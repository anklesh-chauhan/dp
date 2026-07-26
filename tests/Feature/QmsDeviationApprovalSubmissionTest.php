<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\DeviationAuditEvent;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Domain\QMS\Services\DeviationApprovalSubmissionService;
use App\Domain\QMS\Services\QualityApprovalInstancePersistence;
use App\Domain\QMS\Services\QualityApprovalWorkflowSelector;
use App\Exceptions\ModuleNotEnabledException;
use App\Exceptions\WorkflowException;
use App\Models\Department;
use App\Models\SopApproval;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    Permission::findOrCreate('Submit:Deviation', 'web');
    $this->department = Department::factory()->create();
    $this->submitter = User::factory()->create(['department_id' => $this->department]);
    $this->submitter->givePermissionTo('Submit:Deviation');
    $this->deviation = Deviation::factory()->create([
        'department_id' => $this->department,
        'reported_by' => $this->submitter,
    ]);
});

it('installs generic quality workflow persistence without SOP approval coupling', function (): void {
    expect(Schema::hasColumns('quality_approval_workflows', [
        'workflow_code',
        'subject_type',
        'department_id',
        'is_active',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('quality_approval_instances', [
            'instance_uuid',
            'submission_uuid',
            'subject_type',
            'subject_id',
            'workflow_id',
            'workflow_step_id',
            'decision_code',
            'signature_hash',
        ]))->toBeTrue()
        ->and(Schema::hasColumn('quality_approval_instances', 'document_id'))->toBeFalse();
});

it('creates pending Shared approval instances atomically when a workflow is configured', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
    ]);
    $role = Role::findOrCreate('quality reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->count(2)->sequence(
        ['step_no' => 1],
        ['step_no' => 2],
    )->create([
        'workflow_id' => $workflow,
        'role_id' => $role,
    ]);

    $submitted = app(DeviationApprovalSubmissionService::class)->submit(
        $this->deviation,
        $this->submitter,
        'Submit for independent quality review.',
        '203.0.113.40',
        'DocuPharma-QMS-Test/1.0',
    );

    $instances = $submitted->approvalInstances()->orderBy('workflow_step_id')->get();

    expect($submitted->status)->toBe(DeviationStatus::Open)
        ->and($instances)->toHaveCount(2)
        ->and($instances->every(fn (QualityApprovalInstance $instance): bool => $instance->decision_code === 'pending'))->toBeTrue()
        ->and($instances->pluck('workflow_id')->unique()->all())->toBe([$workflow->id])
        ->and($instances->every(fn (QualityApprovalInstance $instance): bool => $instance->approvalInstanceSubject()->is($submitted)))->toBeTrue()
        ->and(DeviationAuditEvent::query()->whereBelongsTo($submitted)->count())->toBe(1)
        ->and(SopApproval::query()->count())->toBe(0);
});

it('prefers a department workflow and falls back to a global subject workflow', function (): void {
    $global = QualityApprovalWorkflow::factory()->create(['department_id' => null]);
    $department = QualityApprovalWorkflow::factory()->create([
        'department_id' => $this->department,
    ]);
    $selector = app(QualityApprovalWorkflowSelector::class);

    expect($selector->selectFor($this->deviation)?->approvalWorkflowDefinitionKey())
        ->toBe($department->id);

    $department->update(['is_active' => false]);

    expect($selector->selectFor($this->deviation)?->approvalWorkflowDefinitionKey())
        ->toBe($global->id);
});

it('preserves direct submission behavior when no quality workflow is configured', function (): void {
    $submitted = app(DeviationApprovalSubmissionService::class)->submit(
        $this->deviation,
        $this->submitter,
        'Submit for triage.',
    );

    expect($submitted->status)->toBe(DeviationStatus::Open)
        ->and($submitted->approvalInstances()->count())->toBe(0)
        ->and($submitted->auditEvents()->count())->toBe(1);
});

it('keeps retries idempotent and preserves signed decisions in a new submission cycle', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create();
    QualityApprovalWorkflowStep::factory()->create(['workflow_id' => $workflow]);
    $persistence = app(QualityApprovalInstancePersistence::class);

    $persistence->initializeFor($this->deviation, $workflow);
    $persistence->initializeFor($this->deviation, $workflow);
    $instance = $this->deviation->approvalInstances()->sole();
    $instance->update([
        'decision_code' => 'approved',
        'decided_by' => $this->submitter->id,
        'decided_at' => now(),
        'signature_hash' => str_repeat('a', 64),
    ]);
    $persistence->initializeFor($this->deviation, $workflow);

    $instances = $this->deviation->approvalInstances()->orderBy('id')->get();

    expect($instances)->toHaveCount(2)
        ->and($instances->first()->decision_code)->toBe('approved')
        ->and($instances->first()->decided_by)->toBe($this->submitter->id)
        ->and($instances->first()->signature_hash)->toBe(str_repeat('a', 64))
        ->and($instances->last()->decision_code)->toBe('pending')
        ->and($instances->pluck('submission_uuid')->unique())->toHaveCount(2);
});

it('rejects empty workflows unauthorized users and disabled QMS without partial state', function (): void {
    QualityApprovalWorkflow::factory()->create(['department_id' => $this->department]);

    expect(fn () => app(DeviationApprovalSubmissionService::class)->submit(
        $this->deviation,
        $this->submitter,
    ))->toThrow(WorkflowException::class)
        ->and($this->deviation->fresh()?->status)->toBe(DeviationStatus::Draft)
        ->and(QualityApprovalInstance::query()->count())->toBe(0);

    QualityApprovalWorkflow::query()->delete();

    expect(fn () => app(DeviationApprovalSubmissionService::class)->submit(
        $this->deviation,
        User::factory()->create(),
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(DeviationApprovalSubmissionService::class)->submit(
        $this->deviation,
        $this->submitter,
    ))->toThrow(ModuleNotEnabledException::class)
        ->and($this->deviation->fresh()?->status)->toBe(DeviationStatus::Draft);
});
