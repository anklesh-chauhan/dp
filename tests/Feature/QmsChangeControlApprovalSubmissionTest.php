<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlAuditEvent;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Domain\QMS\Services\ChangeControlApprovalSubmissionService;
use App\Domain\QMS\Services\QualityApprovalInstancePersistence;
use App\Domain\QMS\Services\QualityApprovalWorkflowSelector;
use App\Exceptions\ModuleNotEnabledException;
use App\Exceptions\WorkflowException;
use App\Models\Department;
use App\Models\SopApproval;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    Permission::findOrCreate('Submit:ChangeControl', 'web');
    $this->department = Department::factory()->create();
    $this->submitter = User::factory()->create(['department_id' => $this->department]);
    $this->submitter->givePermissionTo('Submit:ChangeControl');
    $this->changeControl = ChangeControl::factory()->create([
        'department_id' => $this->department,
        'requested_by' => $this->submitter,
    ]);
});

it('creates pending Shared approval instances atomically when a change control workflow is configured', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
        'subject_type' => ChangeControl::class,
    ]);
    $role = Role::findOrCreate('quality reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->count(2)->sequence(
        ['step_no' => 1],
        ['step_no' => 2],
    )->create([
        'workflow_id' => $workflow,
        'role_id' => $role,
    ]);

    $submitted = app(ChangeControlApprovalSubmissionService::class)->submit(
        $this->changeControl,
        $this->submitter,
        'Submit for independent quality review.',
        '203.0.113.40',
        'QualiGxP-QMS-Test/1.0',
    );

    $instances = $submitted->approvalInstances()->orderBy('workflow_step_id')->get();

    expect($submitted->status)->toBe(ChangeControlStatus::Submitted)
        ->and($instances)->toHaveCount(2)
        ->and($instances->every(fn (QualityApprovalInstance $instance): bool => $instance->decision_code === 'pending'))->toBeTrue()
        ->and($instances->pluck('workflow_id')->unique()->all())->toBe([$workflow->id])
        ->and($instances->every(fn (QualityApprovalInstance $instance): bool => $instance->approvalInstanceSubject()->is($submitted)))->toBeTrue()
        ->and(ChangeControlAuditEvent::query()->whereBelongsTo($submitted)->count())->toBe(1)
        ->and(SopApproval::query()->count())->toBe(0);
});

it('prefers a department change control workflow and ignores a deviation workflow', function (): void {
    $global = QualityApprovalWorkflow::factory()->create([
        'department_id' => null,
        'subject_type' => ChangeControl::class,
    ]);
    $department = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
        'subject_type' => ChangeControl::class,
    ]);
    QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
    ]);
    $selector = app(QualityApprovalWorkflowSelector::class);

    expect($selector->selectFor($this->changeControl)?->approvalWorkflowDefinitionKey())
        ->toBe($department->id);

    $department->update(['is_active' => false]);

    expect($selector->selectFor($this->changeControl)?->approvalWorkflowDefinitionKey())
        ->toBe($global->id);
});

it('preserves direct submission behavior when no change control workflow is configured', function (): void {
    QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
    ]);

    $submitted = app(ChangeControlApprovalSubmissionService::class)->submit(
        $this->changeControl,
        $this->submitter,
        'Submit for triage.',
    );

    expect($submitted->status)->toBe(ChangeControlStatus::Submitted)
        ->and($submitted->approvalInstances()->count())->toBe(0)
        ->and($submitted->auditEvents()->count())->toBe(1);
});

it('keeps retries idempotent and preserves signed decisions in a new submission cycle', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create([
        'subject_type' => ChangeControl::class,
    ]);
    QualityApprovalWorkflowStep::factory()->create(['workflow_id' => $workflow]);
    $persistence = app(QualityApprovalInstancePersistence::class);

    $persistence->initializeFor($this->changeControl, $workflow);
    $persistence->initializeFor($this->changeControl, $workflow);
    $instance = $this->changeControl->approvalInstances()->sole();
    $instance->update([
        'decision_code' => 'approved',
        'decided_by' => $this->submitter->id,
        'decided_at' => now(),
        'signature_hash' => str_repeat('a', 64),
    ]);
    $persistence->initializeFor($this->changeControl, $workflow);

    $instances = $this->changeControl->approvalInstances()->orderBy('id')->get();

    expect($instances)->toHaveCount(2)
        ->and($instances->first()->decision_code)->toBe('approved')
        ->and($instances->first()->decided_by)->toBe($this->submitter->id)
        ->and($instances->first()->signature_hash)->toBe(str_repeat('a', 64))
        ->and($instances->last()->decision_code)->toBe('pending')
        ->and($instances->pluck('submission_uuid')->unique())->toHaveCount(2);
});

it('rejects empty workflows unauthorized users and disabled QMS without partial state', function (): void {
    QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
        'subject_type' => ChangeControl::class,
    ]);

    expect(fn () => app(ChangeControlApprovalSubmissionService::class)->submit(
        $this->changeControl,
        $this->submitter,
    ))->toThrow(WorkflowException::class)
        ->and($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Draft)
        ->and(QualityApprovalInstance::query()->count())->toBe(0);

    QualityApprovalWorkflow::query()->delete();

    expect(fn () => app(ChangeControlApprovalSubmissionService::class)->submit(
        $this->changeControl,
        User::factory()->create(),
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(ChangeControlApprovalSubmissionService::class)->submit(
        $this->changeControl,
        $this->submitter,
    ))->toThrow(ModuleNotEnabledException::class)
        ->and($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Draft);
});
