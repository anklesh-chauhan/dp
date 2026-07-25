<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use App\Models\ApprovalDecision;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
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

uses(RefreshDatabase::class);

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

    app(WorkflowEngineService::class)->start($document, $submitter, $workflowDefinition);

    $existingApproval->refresh();

    expect($existingApproval->approval_decision_id)->toBe(ApprovalDecision::idFor(ApprovalDecision::PENDING))
        ->and($existingApproval->approved_by)->toBeNull()
        ->and($existingApproval->comments)->toBeNull()
        ->and($existingApproval->approved_at)->toBeNull()
        ->and($existingApproval->signature_hash)->toBeNull()
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
