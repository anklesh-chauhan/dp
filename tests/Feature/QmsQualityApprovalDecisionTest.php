<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Domain\QMS\Services\DeviationApprovalDecisionService;
use App\Domain\QMS\Services\DeviationApprovalSubmissionService;
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
        'Submit:Deviation',
        'Investigate:Deviation',
        'Decide:QualityApproval',
        'Manage:Deviation',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->department = Department::factory()->create();
    $this->submitter = User::factory()->create(['department_id' => $this->department]);
    $this->submitter->givePermissionTo('Submit:Deviation');
    $this->deviation = Deviation::factory()->create([
        'department_id' => $this->department,
        'reported_by' => $this->submitter,
    ]);
    $this->workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
    ]);
});

it('enforces ordered roles and signs every approval before investigation begins', function (): void {
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
    $firstReviewer = reviewerForQualityApproval($firstRole, $this->department);
    $secondReviewer = reviewerForQualityApproval($secondRole, $this->department);
    app(DeviationApprovalSubmissionService::class)->submit(
        $this->deviation,
        $this->submitter,
        'Submit for two-level review.',
    );
    [$first, $second] = $this->deviation->approvalInstances()
        ->orderBy('workflow_step_id')
        ->get()
        ->all();
    $service = app(DeviationApprovalDecisionService::class);

    expect(fn () => $service->approve($second, $secondReviewer, 'Second review attempted early.'))
        ->toThrow(WorkflowException::class);

    $approvedFirst = $service->approve($first, $firstReviewer, 'Initial quality review approved.');

    expect($this->deviation->fresh()?->status)->toBe(DeviationStatus::Open)
        ->and($approvedFirst->decision_code)->toBe('approved')
        ->and($approvedFirst->signatureSignerId())->toBe($firstReviewer->id)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvedFirst))->toBeTrue();

    expect(fn () => $service->approve($approvedFirst, $firstReviewer, 'Duplicate decision.'))
        ->toThrow(WorkflowException::class);

    $approvedSecond = $service->approve($second->fresh(), $secondReviewer, 'Final quality review approved.');

    expect($this->deviation->fresh()?->status)->toBe(DeviationStatus::UnderInvestigation)
        ->and($approvedSecond->decision_code)->toBe('approved')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvedSecond))->toBeTrue()
        ->and($this->deviation->approvalInstances()->where('decision_code', 'approved')->count())->toBe(2)
        ->and($this->deviation->auditEvents()->where('to_status', DeviationStatus::UnderInvestigation->value)->count())->toBe(1);
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
    $reviewer = reviewerForQualityApproval($role, $this->department);
    app(DeviationApprovalSubmissionService::class)->submit($this->deviation, $this->submitter);
    $instance = $this->deviation->approvalInstances()->orderBy('workflow_step_id')->firstOrFail();

    $rejected = app(DeviationApprovalDecisionService::class)->reject(
        $instance,
        $reviewer,
        'Insufficient immediate containment evidence.',
    );

    expect($this->deviation->fresh()?->status)->toBe(DeviationStatus::Rejected)
        ->and($rejected->decision_code)->toBe('rejected')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($rejected))->toBeTrue()
        ->and($this->deviation->approvalInstances()->where('decision_code', 'not_required')->count())->toBe(1)
        ->and($this->deviation->auditEvents()->latest('id')->firstOrFail()->signature_hash)->not->toBeNull();
});

it('returns a deviation to editable draft with signed approval and lifecycle history', function (): void {
    $role = Role::findOrCreate('quality return reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'role_id' => $role,
    ]);
    $reviewer = reviewerForQualityApproval($role, $this->department);
    app(DeviationApprovalSubmissionService::class)->submit($this->deviation, $this->submitter);
    $instance = $this->deviation->approvalInstances()->sole();

    $returned = app(DeviationApprovalDecisionService::class)->return(
        $instance,
        $reviewer,
        'Clarify the event chronology before resubmission.',
    );

    expect($this->deviation->fresh()?->status)->toBe(DeviationStatus::Draft)
        ->and($returned->decision_code)->toBe('returned')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($returned))->toBeTrue()
        ->and($this->deviation->auditEvents()->where('to_status', DeviationStatus::Draft->value)->count())->toBe(1)
        ->and(app(ElectronicSignatureVerifier::class)->isValid(
            $this->deviation->auditEvents()->latest('id')->firstOrFail(),
        ))->toBeTrue();
});

it('enforces permission role department separation of duties and QMS entitlement', function (): void {
    $role = Role::findOrCreate('restricted quality reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'role_id' => $role,
    ]);
    $this->submitter->assignRole($role);
    $this->submitter->givePermissionTo(['Decide:QualityApproval', 'Investigate:Deviation']);
    app(DeviationApprovalSubmissionService::class)->submit($this->deviation, $this->submitter);
    $instance = $this->deviation->approvalInstances()->sole();
    $service = app(DeviationApprovalDecisionService::class);

    expect(fn () => $service->approve($instance, $this->submitter))
        ->toThrow(WorkflowException::class);

    $wrongRole = User::factory()->create(['department_id' => $this->department]);
    $wrongRole->givePermissionTo(['Decide:QualityApproval', 'Investigate:Deviation']);

    expect(fn () => $service->approve($instance, $wrongRole))
        ->toThrow(WorkflowException::class);

    $crossDepartment = reviewerForQualityApproval($role, Department::factory()->create());

    expect(fn () => $service->approve($instance, $crossDepartment))
        ->toThrow(WorkflowException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->approve(
        $instance,
        reviewerForQualityApproval($role, $this->department),
    ))
        ->toThrow(ModuleNotEnabledException::class);
});

it('does not persist a decision without authority for its Deviation outcome', function (): void {
    $role = Role::findOrCreate('decision only reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'role_id' => $role,
    ]);
    $reviewer = User::factory()->create(['department_id' => $this->department]);
    $reviewer->assignRole($role);
    $reviewer->givePermissionTo('Decide:QualityApproval');
    app(DeviationApprovalSubmissionService::class)->submit($this->deviation, $this->submitter);
    $instance = $this->deviation->approvalInstances()->sole();

    expect(fn () => app(DeviationApprovalDecisionService::class)->approve(
        $instance,
        $reviewer,
        'Approval without lifecycle authority.',
    ))->toThrow(WorkflowException::class)
        ->and($instance->fresh()?->decision_code)->toBe('pending')
        ->and($instance->fresh()?->signature_hash)->toBeNull()
        ->and($this->deviation->fresh()?->status)->toBe(DeviationStatus::Open);
});

function reviewerForQualityApproval(
    Role $role,
    Department $department,
): User {
    $reviewer = User::factory()->create(['department_id' => $department]);
    $reviewer->assignRole($role);
    $reviewer->givePermissionTo(['Decide:QualityApproval', 'Investigate:Deviation']);

    return $reviewer;
}
