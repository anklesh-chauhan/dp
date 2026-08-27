<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Domain\QMS\Services\ChangeControlApprovalDecisionService;
use App\Domain\QMS\Services\ChangeControlApprovalSubmissionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Exceptions\WorkflowException;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    foreach ([
        'Submit:ChangeControl',
        'Review:ChangeControl',
        'Approve:ChangeControl',
        'Decide:QualityApproval',
        'Manage:ChangeControl',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->department = Department::factory()->create();
    $this->submitter = User::factory()->create(['department_id' => $this->department]);
    $this->submitter->givePermissionTo('Submit:ChangeControl');
    $this->changeControl = ChangeControl::factory()->create([
        'department_id' => $this->department,
        'requested_by' => $this->submitter,
    ]);
    $this->workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
        'subject_type' => ChangeControl::class,
    ]);
});

it('enforces ordered roles and signs every approval before the change control is approved', function (): void {
    $firstRole = Role::findOrCreate('quality reviewer level one', 'web');
    $secondRole = Role::findOrCreate('quality reviewer level two', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'step_no' => 1,
        'role_id' => $firstRole,
    ]);
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'step_no' => 2,
        'role_id' => $secondRole,
    ]);
    $firstReviewer = reviewerForChangeControlQualityApproval($firstRole, $this->department);
    $secondReviewer = reviewerForChangeControlQualityApproval($secondRole, $this->department);
    app(ChangeControlApprovalSubmissionService::class)->submit(
        $this->changeControl,
        $this->submitter,
        'Submit for two-level review.',
    );
    [$first, $second] = $this->changeControl->approvalInstances()
        ->orderBy('workflow_step_id')
        ->get()
        ->all();
    $service = app(ChangeControlApprovalDecisionService::class);

    expect(fn () => $service->approve($second, $secondReviewer, 'Second review attempted early.'))
        ->toThrow(WorkflowException::class);

    $approvedFirst = $service->approve($first, $firstReviewer, 'Initial quality review approved.');

    expect($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Submitted)
        ->and($approvedFirst->decision_code)->toBe('approved')
        ->and($approvedFirst->signatureSignerId())->toBe($firstReviewer->id)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvedFirst))->toBeTrue();

    expect(fn () => $service->approve($approvedFirst, $firstReviewer, 'Duplicate decision.'))
        ->toThrow(WorkflowException::class);

    $approvedSecond = $service->approve($second->fresh(), $secondReviewer, 'Final quality review approved.');

    expect($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Approved)
        ->and($approvedSecond->decision_code)->toBe('approved')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvedSecond))->toBeTrue()
        ->and($this->changeControl->approvalInstances()->where('decision_code', 'approved')->count())->toBe(2)
        ->and($this->changeControl->auditEvents()->where('to_status', ChangeControlStatus::Approved->value)->count())->toBe(1);
});

it('applies signed rejection and makes remaining steps not required', function (): void {
    $role = Role::findOrCreate('quality rejection reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->count(2)->sequence(
        ['step_no' => 1],
        ['step_no' => 2],
    )->create([
        'workflow_id' => $this->workflow,
        'role_id' => $role,
    ]);
    $reviewer = reviewerForChangeControlQualityApproval($role, $this->department);
    app(ChangeControlApprovalSubmissionService::class)->submit($this->changeControl, $this->submitter);
    $instance = $this->changeControl->approvalInstances()->orderBy('workflow_step_id')->firstOrFail();

    $rejected = app(ChangeControlApprovalDecisionService::class)->reject(
        $instance,
        $reviewer,
        'Insufficient change impact evidence.',
    );

    expect($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Rejected)
        ->and($rejected->decision_code)->toBe('rejected')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($rejected))->toBeTrue()
        ->and($this->changeControl->approvalInstances()->where('decision_code', 'not_required')->count())->toBe(1)
        ->and($this->changeControl->auditEvents()->latest('id')->firstOrFail()->signature_hash)->not->toBeNull();
});

it('returns a change control to editable draft with signed approval and lifecycle history', function (): void {
    $role = Role::findOrCreate('quality return reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'role_id' => $role,
    ]);
    $reviewer = reviewerForChangeControlQualityApproval($role, $this->department);
    app(ChangeControlApprovalSubmissionService::class)->submit($this->changeControl, $this->submitter);
    $instance = $this->changeControl->approvalInstances()->sole();

    $returned = app(ChangeControlApprovalDecisionService::class)->return(
        $instance,
        $reviewer,
        'Clarify the validation rationale before resubmission.',
    );

    expect($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Draft)
        ->and($returned->decision_code)->toBe('returned')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($returned))->toBeTrue()
        ->and($this->changeControl->auditEvents()->where('to_status', ChangeControlStatus::Draft->value)->count())->toBe(1)
        ->and(app(ElectronicSignatureVerifier::class)->isValid(
            $this->changeControl->auditEvents()->latest('id')->firstOrFail(),
        ))->toBeTrue();
});

it('enforces permission role department separation of duties and QMS entitlement', function (): void {
    $role = Role::findOrCreate('restricted quality reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'role_id' => $role,
    ]);
    $this->submitter->assignRole($role);
    $this->submitter->givePermissionTo(['Decide:QualityApproval', 'Review:ChangeControl', 'Approve:ChangeControl']);
    app(ChangeControlApprovalSubmissionService::class)->submit($this->changeControl, $this->submitter);
    $instance = $this->changeControl->approvalInstances()->sole();
    $service = app(ChangeControlApprovalDecisionService::class);

    expect(fn () => $service->approve($instance, $this->submitter))
        ->toThrow(WorkflowException::class);

    $wrongRole = User::factory()->create(['department_id' => $this->department]);
    $wrongRole->givePermissionTo(['Decide:QualityApproval', 'Review:ChangeControl', 'Approve:ChangeControl']);

    expect(fn () => $service->approve($instance, $wrongRole))
        ->toThrow(WorkflowException::class);

    $crossDepartment = reviewerForChangeControlQualityApproval($role, Department::factory()->create());

    expect(fn () => $service->approve($instance, $crossDepartment))
        ->toThrow(WorkflowException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->approve(
        $instance,
        reviewerForChangeControlQualityApproval($role, $this->department),
    ))
        ->toThrow(ModuleNotEnabledException::class);
});

it('does not persist a decision without authority for its Change Control outcome', function (): void {
    $role = Role::findOrCreate('decision only reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'role_id' => $role,
    ]);
    $reviewer = User::factory()->create(['department_id' => $this->department]);
    $reviewer->assignRole($role);
    $reviewer->givePermissionTo('Decide:QualityApproval');
    app(ChangeControlApprovalSubmissionService::class)->submit($this->changeControl, $this->submitter);
    $instance = $this->changeControl->approvalInstances()->sole();

    expect(fn () => app(ChangeControlApprovalDecisionService::class)->approve(
        $instance,
        $reviewer,
        'Approval without lifecycle authority.',
    ))->toThrow(WorkflowException::class)
        ->and($instance->fresh()?->decision_code)->toBe('pending')
        ->and($instance->fresh()?->signature_hash)->toBeNull()
        ->and($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Submitted);
});

function reviewerForChangeControlQualityApproval(
    Role $role,
    Department $department,
): User {
    $reviewer = User::factory()->create(['department_id' => $department]);
    $reviewer->assignRole($role);
    $reviewer->givePermissionTo(['Decide:QualityApproval', 'Review:ChangeControl', 'Approve:ChangeControl']);

    return $reviewer;
}
