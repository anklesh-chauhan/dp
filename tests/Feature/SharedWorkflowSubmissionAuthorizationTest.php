<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use App\Domain\Shared\Services\ApprovalWorkflowEngineService;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalDecision;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopRole;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\Sop\WorkflowEngineService;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('resolves canonical and legacy workflow engine entry points', function (): void {
    expect(app(ApprovalWorkflowEngineService::class))
        ->toBeInstanceOf(ApprovalWorkflowEngineService::class)
        ->and(app(WorkflowEngineService::class))
        ->toBeInstanceOf(ApprovalWorkflowEngineService::class)
        ->toBeInstanceOf(WorkflowEngineService::class);
});

it('authorizes submission through the Shared approvable subject contract', function (): void {
    Permission::findOrCreate('Submit:SopDocument', 'web');

    $owner = User::factory()->create();
    $owner->givePermissionTo('Submit:SopDocument');

    $otherUser = User::factory()->create();
    $otherUser->givePermissionTo('Submit:SopDocument');

    $subject = new class($owner->id) implements ApprovableSubject
    {
        public function __construct(private readonly int $ownerId) {}

        public function approvalSubjectKey(): int|string|null
        {
            return 'quality-event-42';
        }

        public function approvalSubjectReference(): string
        {
            return 'QMS-DEV-00042';
        }

        public function approvalSubjectTitle(): string
        {
            return 'Temperature excursion';
        }

        public function approvalSubjectDepartmentId(): ?int
        {
            return null;
        }

        public function approvalSubjectCreatedById(): ?int
        {
            return null;
        }

        public function approvalSubjectOwnerId(): ?int
        {
            return $this->ownerId;
        }
    };

    $workflow = app(WorkflowEngineService::class);

    expect($workflow->canSubmit($subject, $owner))->toBeTrue()
        ->and($workflow->canSubmit($subject, $otherUser))->toBeFalse();
});

it('rejects a Shared approvable subject when the user lacks submission permission', function (): void {
    $subject = new class implements ApprovableSubject
    {
        public function approvalSubjectKey(): int|string|null
        {
            return 'quality-event-43';
        }

        public function approvalSubjectReference(): string
        {
            return 'QMS-DEV-00043';
        }

        public function approvalSubjectTitle(): string
        {
            return 'Unapproved deviation';
        }

        public function approvalSubjectDepartmentId(): ?int
        {
            return null;
        }

        public function approvalSubjectCreatedById(): ?int
        {
            return null;
        }

        public function approvalSubjectOwnerId(): ?int
        {
            return null;
        }
    };

    expect(app(WorkflowEngineService::class)->canSubmit(
        $subject,
        User::factory()->create(),
    ))->toBeFalse();
});

it('preserves SOP submission permission, role, department, creator, and owner rules', function (): void {
    Permission::findOrCreate('Submit:SopDocument', 'web');
    Permission::findOrCreate('Update:SopDocument', 'web');

    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $creator = User::factory()->create(['department_id' => $department->id]);
    $owner = User::factory()->create(['department_id' => $department->id]);
    $subject = new class($department->id, $creator->id, $owner->id) implements ApprovableSubject
    {
        public function __construct(
            private readonly int $departmentId,
            private readonly int $creatorId,
            private readonly int $ownerId,
        ) {}

        public function approvalSubjectKey(): int|string|null
        {
            return 'quality-event-authorization';
        }

        public function approvalSubjectReference(): string
        {
            return 'QMS-DEV-AUTH';
        }

        public function approvalSubjectTitle(): string
        {
            return 'Submission authorization';
        }

        public function approvalSubjectDepartmentId(): ?int
        {
            return $this->departmentId;
        }

        public function approvalSubjectCreatedById(): ?int
        {
            return $this->creatorId;
        }

        public function approvalSubjectOwnerId(): ?int
        {
            return $this->ownerId;
        }
    };

    $administrator = User::factory()->create(['department_id' => $otherDepartment->id]);
    $administrator->givePermissionTo('Submit:SopDocument');
    $administrator->assignRole(Role::findOrCreate(SopRole::ADMINISTRATOR, 'web'));

    $administratorWithoutPermission = User::factory()->create();
    $administratorWithoutPermission->assignRole(Role::findOrCreate(SopRole::ADMINISTRATOR, 'web'));

    $maker = User::factory()->create(['department_id' => $department->id]);
    $maker->givePermissionTo('Submit:SopDocument');
    $maker->assignRole(Role::findOrCreate(SopRole::MAKER, 'web'));

    $otherDepartmentMaker = User::factory()->create(['department_id' => $otherDepartment->id]);
    $otherDepartmentMaker->givePermissionTo('Submit:SopDocument');
    $otherDepartmentMaker->assignRole(Role::findOrCreate(SopRole::MAKER, 'web'));

    $unscopedMaker = User::factory()->create(['department_id' => null]);
    $unscopedMaker->givePermissionTo('Submit:SopDocument');
    $unscopedMaker->assignRole(Role::findOrCreate(SopRole::MAKER, 'web'));

    $creator->givePermissionTo('Update:SopDocument');
    $owner->givePermissionTo('Submit:SopDocument');

    $unrelatedUser = User::factory()->create(['department_id' => $department->id]);
    $unrelatedUser->givePermissionTo('Submit:SopDocument');

    $authorization = app(ApprovalSubmissionAuthorization::class);
    $workflow = app(WorkflowEngineService::class);

    expect($authorization->canSubmit($subject, $administrator))->toBeTrue()
        ->and($authorization->canSubmit($subject, $administratorWithoutPermission))->toBeFalse()
        ->and($authorization->canSubmit($subject, $maker))->toBeTrue()
        ->and($authorization->canSubmit($subject, $otherDepartmentMaker))->toBeFalse()
        ->and($authorization->canSubmit($subject, $unscopedMaker))->toBeTrue()
        ->and($authorization->canSubmit($subject, $creator))->toBeTrue()
        ->and($authorization->canSubmit($subject, $owner))->toBeTrue()
        ->and($authorization->canSubmit($subject, $unrelatedUser))->toBeFalse()
        ->and($workflow->canSubmit($subject, $administrator))->toBeTrue()
        ->and($workflow->canSubmit($subject, $otherDepartmentMaker))->toBeFalse();
});

it('selects a department workflow through Shared approvable subject metadata', function (): void {
    $department = Department::factory()->create();
    $globalWorkflow = SopWorkflow::factory()->create(['department_id' => null]);
    $departmentWorkflow = SopWorkflow::factory()->create(['department_id' => $department->id]);

    $subject = Mockery::mock(ApprovableSubject::class, function (MockInterface $mock) use ($department): void {
        $mock->shouldReceive('approvalSubjectDepartmentId')
            ->once()
            ->andReturn($department->id);
    });

    $resolvedWorkflow = app(WorkflowEngineService::class)->resolveWorkflow($subject);

    expect($resolvedWorkflow?->is($departmentWorkflow))->toBeTrue()
        ->and($resolvedWorkflow?->is($globalWorkflow))->toBeFalse();
});

it('falls back to the global SOP workflow for a Shared subject without a department workflow', function (): void {
    $department = Department::factory()->create();
    $globalWorkflow = SopWorkflow::factory()->create(['department_id' => null]);

    $subject = Mockery::mock(ApprovableSubject::class, function (MockInterface $mock) use ($department): void {
        $mock->shouldReceive('approvalSubjectDepartmentId')
            ->once()
            ->andReturn($department->id);
    });

    $resolvedWorkflow = app(WorkflowEngineService::class)->resolveWorkflow($subject);

    expect($resolvedWorkflow?->is($globalWorkflow))->toBeTrue();
});

it('persists the existing SOP approval workflow from Shared definition metadata', function (): void {
    $this->seed(LookupTableSeeder::class);
    Permission::findOrCreate('Submit:SopDocument', 'web');

    $submitter = User::factory()->create();
    $submitter->givePermissionTo('Submit:SopDocument');

    $department = Department::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $documentType->update(['category_id' => DocumentCategory::factory()->create()->id]);
    $template = SopTemplate::query()->create([
        'name' => 'Shared Workflow Execution Template',
        'code' => 'TPL-SHARED-WORKFLOW',
        'department_id' => $department->id,
        'document_type_id' => $documentType->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'current_version' => 1,
        'created_by' => $submitter->id,
    ]);
    $templateVersion = SopTemplateVersion::query()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
        'content_json' => [],
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'created_by' => $submitter->id,
    ]);
    $document = SopDocument::query()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'document_number' => 'SOP-SHARED-00001',
        'title' => 'Shared workflow execution',
        'department_id' => $department->id,
        'document_type_id' => $documentType->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $submitter->id,
        'owner_id' => $submitter->id,
    ]);
    $workflow = SopWorkflow::factory()->create();
    $step = SopWorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'step_no' => 1,
    ]);
    $existingApproval = SopApproval::query()->create([
        'document_id' => $document->id,
        'workflow_step_id' => $step->id,
        'approved_by' => $submitter->id,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
        'comments' => 'Previous review',
        'approved_at' => now(),
        'signature_hash' => 'previous-signature',
    ]);

    expect($existingApproval)
        ->toBeInstanceOf(ApprovalInstance::class)
        ->and($existingApproval->approvalInstanceKey())->toBe($existingApproval->id)
        ->and($existingApproval->approvalInstanceSubject()->is($document))->toBeTrue()
        ->and($existingApproval->approvalInstanceWorkflowStepDefinition()->is($step))->toBeTrue()
        ->and($existingApproval->approvalInstanceDecisionCode())->toBe(ApprovalDecision::APPROVED)
        ->and($existingApproval->approvalInstanceApproverId())->toBe($submitter->id)
        ->and($existingApproval->approvalInstanceComments())->toBe('Previous review')
        ->and($existingApproval->approvalInstanceDecidedAt())->not->toBeNull()
        ->and($existingApproval->approvalInstanceSignatureHash())->toBe('previous-signature');

    $decisionTime = now()->subMinute();
    $recordedApproval = app(ApprovalDecisionPersistence::class)->recordDecision(
        approval: $existingApproval,
        decisionCode: ApprovalDecision::REJECTED,
        decidedById: $submitter->id,
        comments: 'Rejected through Shared persistence.',
        decidedAt: $decisionTime,
    );
    $recordedApproval->refresh();

    expect($recordedApproval)->toBe($existingApproval)
        ->and($recordedApproval->approvalInstanceDecisionCode())->toBe(ApprovalDecision::REJECTED)
        ->and($recordedApproval->approvalInstanceApproverId())->toBe($submitter->id)
        ->and($recordedApproval->approvalInstanceComments())->toBe('Rejected through Shared persistence.')
        ->and($recordedApproval->approvalInstanceDecidedAt()?->format('Y-m-d H:i:s'))
        ->toBe($decisionTime->format('Y-m-d H:i:s'))
        ->and($recordedApproval->approvalInstanceSignatureHash())->toBe('previous-signature');

    $stepDefinition = new class($step->id) implements ApprovalWorkflowStepDefinition
    {
        public function __construct(private readonly int $stepId) {}

        public function approvalWorkflowStepDefinitionKey(): int|string|null
        {
            return $this->stepId;
        }
    };

    $workflowDefinition = new class($workflow->id, $stepDefinition) implements ApprovalWorkflowDefinition
    {
        public function __construct(
            private readonly int $workflowId,
            private readonly ApprovalWorkflowStepDefinition $step,
        ) {}

        public function approvalWorkflowDefinitionKey(): int|string|null
        {
            return $this->workflowId;
        }

        /**
         * @return iterable<ApprovalWorkflowStepDefinition>
         */
        public function approvalWorkflowStepDefinitions(): iterable
        {
            return [$this->step];
        }
    };

    $document->update([
        'locked_by' => $submitter->id,
        'locked_at' => now(),
    ]);
    $submissionLifecycle = app(ApprovalSubmissionLifecycle::class);
    $failingLifecycle = Mockery::mock(ApprovalSubmissionLifecycle::class);
    $failingLifecycle->shouldReceive('assertSubmittable')
        ->once()
        ->andReturnUsing(fn (ApprovableSubject $subject) => $submissionLifecycle->assertSubmittable($subject));
    $failingLifecycle->shouldReceive('prepareSubmission')
        ->once()
        ->andReturnUsing(fn (ApprovableSubject $subject, User $user) => $submissionLifecycle->prepareSubmission($subject, $user));
    $failingLifecycle->shouldReceive('markSubmitted')
        ->once()
        ->andThrow(new RuntimeException('Submission completion failed.'));
    app()->instance(ApprovalSubmissionLifecycle::class, $failingLifecycle);

    expect(fn () => app(WorkflowEngineService::class)
        ->start($document, $submitter, $workflowDefinition))
        ->toThrow(RuntimeException::class, 'Submission completion failed.');

    expect($existingApproval->refresh()->approvalInstanceDecisionCode())->toBe(ApprovalDecision::REJECTED)
        ->and($document->refresh()->locked_by)->toBe($submitter->id)
        ->and($document->documentStatus?->code)->toBe(DocumentStatus::DRAFT)
        ->and(SopAuditLog::query()
            ->where('document_id', $document->id)
            ->whereIn('action', [SopAuditLog::ACTION_UNLOCKED, SopAuditLog::ACTION_SUBMITTED])
            ->exists())->toBeFalse();

    app()->forgetInstance(ApprovalSubmissionLifecycle::class);

    app(WorkflowEngineService::class)->start($document, $submitter, $workflowDefinition);

    $existingApproval->refresh();

    expect($existingApproval->approval_decision_id)->toBe(ApprovalDecision::idFor(ApprovalDecision::PENDING))
        ->and($existingApproval->approved_by)->toBeNull()
        ->and($existingApproval->comments)->toBeNull()
        ->and($existingApproval->approved_at)->toBeNull()
        ->and($existingApproval->signature_hash)->toBeNull()
        ->and($document->refresh()->locked_by)->toBeNull()
        ->and($document->locked_at)->toBeNull()
        ->and(SopApproval::query()
            ->where('document_id', $document->id)
            ->where('workflow_step_id', $step->id)
            ->count())->toBe(1)
        ->and($document->refresh()->documentStatus?->code)->toBe(DocumentStatus::UNDER_REVIEW);

    $auditLog = SopAuditLog::query()
        ->where('document_id', $document->id)
        ->where('action', SopAuditLog::ACTION_SUBMITTED)
        ->firstOrFail();

    expect($auditLog->new_values)
        ->toMatchArray([
            'workflow_id' => $workflow->id,
            'submitted_by' => $submitter->id,
        ]);
});

it('rejects non-DMS subjects at the SOP submission lifecycle boundary', function (): void {
    $subject = Mockery::mock(ApprovableSubject::class);

    expect(fn () => app(ApprovalSubmissionLifecycle::class)->assertSubmittable($subject))
        ->toThrow(
            InvalidArgumentException::class,
            'The SOP approval submission lifecycle adapter requires a SopDocument subject.',
        );
});

it('preserves SOP draft validation at the submission lifecycle boundary', function (): void {
    $document = new SopDocument;
    $document->setRelation('documentStatus', new DocumentStatus([
        'code' => DocumentStatus::APPROVED,
        'name' => 'Approved',
    ]));

    expect(fn () => app(ApprovalSubmissionLifecycle::class)->assertSubmittable($document))
        ->toThrow(
            WorkflowException::class,
            'Only draft documents can be submitted for approval.',
        );
});

it('rejects non-DMS subjects at the SOP approval persistence adapter boundary', function (): void {
    $subject = Mockery::mock(ApprovableSubject::class);
    $workflow = Mockery::mock(ApprovalWorkflowDefinition::class);

    expect(function () use ($subject, $workflow): void {
        app(ApprovalInstancePersistence::class)->initializeFor($subject, $workflow);
    })
        ->toThrow(
            InvalidArgumentException::class,
            'The SOP approval persistence adapter requires a SopDocument subject.',
        );
});

it('routes Shared approval instances through configured decision boundaries', function (
    string $decisionMethod,
    string $decisionCode,
): void {
    $approval = Mockery::mock(ApprovalInstance::class);
    $approver = User::factory()->create();

    if ($decisionMethod === 'approve') {
        $approval->shouldReceive('approvalInstanceKey')
            ->once()
            ->andReturn('shared-approval-42');
    }

    $authorization = Mockery::mock(ApprovalDecisionAuthorization::class);
    $authorization->shouldReceive('authorizeDecision')
        ->once()
        ->with($approval, $approver);

    $persistence = Mockery::mock(ApprovalDecisionPersistence::class);
    $persistence->shouldReceive('recordDecision')
        ->once()
        ->withArgs(fn (
            ApprovalInstance $instance,
            string $code,
            int $decidedById,
        ): bool => $instance === $approval
            && $code === $decisionCode
            && $decidedById === $approver->id)
        ->andReturn($approval);

    $outcome = Mockery::mock(ApprovalDecisionOutcome::class);
    $outcome->shouldReceive('applyOutcome')
        ->once()
        ->with($approval, $decisionCode, $approver)
        ->andReturn($approval);

    app()->instance(ApprovalDecisionAuthorization::class, $authorization);
    app()->instance(ApprovalDecisionPersistence::class, $persistence);
    app()->instance(ApprovalDecisionOutcome::class, $outcome);

    $result = app(WorkflowEngineService::class)->{$decisionMethod}($approval, $approver);

    expect($result)->toBe($approval);
})->with([
    'approve' => ['approve', ApprovalDecision::APPROVED],
    'reject' => ['reject', ApprovalDecision::REJECTED],
    'return' => ['return', ApprovalDecision::RETURNED],
]);

it('rejects non-SOP instances at the approval decision persistence boundary', function (): void {
    $approval = Mockery::mock(ApprovalInstance::class);

    expect(function () use ($approval): void {
        app(ApprovalDecisionPersistence::class)->recordDecision(
            approval: $approval,
            decisionCode: ApprovalDecision::REJECTED,
            decidedById: 42,
            comments: null,
            decidedAt: now(),
        );
    })->toThrow(
        InvalidArgumentException::class,
        'The SOP approval decision adapter requires a SopApproval instance.',
    );
});
