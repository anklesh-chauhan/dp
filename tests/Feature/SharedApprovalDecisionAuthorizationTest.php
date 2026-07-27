<?php

declare(strict_types=1);

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\RejectDocumentAction;
use App\Actions\Sop\ReturnDocumentAction;
use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\WorkflowException;
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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    Permission::findOrCreate('Approve:SopDocument', 'web');

    $this->department = Department::factory()->create();
    $this->approverRole = Role::findOrCreate('qa reviewer', 'web');
    $this->creator = User::factory()->create(['department_id' => $this->department->id]);
    $this->approver = User::factory()->create(['department_id' => $this->department->id]);
    $this->approver->givePermissionTo('Approve:SopDocument');
    $this->approver->assignRole($this->approverRole);

    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $documentType->update(['category_id' => DocumentCategory::factory()->create()->id]);
    $template = SopTemplate::query()->create([
        'name' => 'Authorization Boundary Template',
        'code' => 'TPL-AUTHORIZATION-BOUNDARY',
        'department_id' => $this->department->id,
        'document_type_id' => $documentType->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'current_version' => 1,
        'created_by' => $this->creator->id,
    ]);
    $templateVersion = SopTemplateVersion::query()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
        'content_json' => [],
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'created_by' => $this->creator->id,
    ]);
    $this->document = SopDocument::query()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'document_number' => 'SOP-AUTH-00001',
        'title' => 'Shared authorization boundary',
        'department_id' => $this->department->id,
        'document_type_id' => $documentType->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        'created_by' => $this->creator->id,
        'owner_id' => $this->creator->id,
    ]);
    $this->workflow = SopWorkflow::factory()->create();
    $this->step = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow->id,
        'step_no' => 1,
        'role_id' => $this->approverRole->id,
        'department_id' => $this->department->id,
        'is_mandatory' => true,
    ]);
    $this->approval = SopApproval::factory()->create([
        'document_id' => $this->document->id,
        'workflow_step_id' => $this->step->id,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'comments' => null,
        'approved_at' => null,
        'signature_hash' => null,
        'signature_ip_address' => null,
        'signature_user_agent' => null,
    ]);
});

it('authorizes an actionable SOP approval through the Shared boundary', function (): void {
    app(ApprovalDecisionAuthorization::class)->authorizeDecision($this->approval, $this->approver);

    expect($this->approval->canBeApprovedBy($this->approver))->toBeTrue();
});

it('rejects non-SOP approval instances at the DMS authorization adapter', function (): void {
    $approval = Mockery::mock(ApprovalInstance::class);

    expect(fn () => app(ApprovalDecisionAuthorization::class)
        ->authorizeDecision($approval, $this->approver))
        ->toThrow(
            WorkflowException::class,
            'The SOP approval authorization adapter requires a SopApproval instance.',
        );
});

it('preserves SOP decision authorization rules', function (Closure $arrange, string $message): void {
    $arrange->call($this);

    expect(fn () => app(ApprovalDecisionAuthorization::class)
        ->authorizeDecision($this->approval, $this->approver))
        ->toThrow(WorkflowException::class, $message);
})->with([
    'permission' => [
        function (): void {
            $this->approver->revokePermissionTo('Approve:SopDocument');
        },
        'You do not have permission to approve SOP documents.',
    ],
    'role' => [
        function (): void {
            $this->approver->removeRole($this->approverRole);
        },
        "Only users with the 'qa reviewer' role can approve this step.",
    ],
    'separation of duties' => [
        function (): void {
            $this->document->update(['created_by' => $this->approver->id]);
        },
        'You cannot approve this document because of the separation of duties policy.',
    ],
    'department scope' => [
        function (): void {
            $otherDepartment = Department::factory()->create();
            $this->approver->update(['department_id' => $otherDepartment->id]);
        },
        'You can only approve documents for your own department.',
    ],
]);

it('preserves mandatory step ordering', function (): void {
    $this->step->update(['step_no' => 2]);
    $priorStep = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow->id,
        'step_no' => 1,
        'role_id' => $this->approverRole->id,
        'department_id' => $this->department->id,
        'is_mandatory' => true,
    ]);
    SopApproval::factory()->create([
        'document_id' => $this->document->id,
        'workflow_step_id' => $priorStep->id,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
    ]);

    expect(fn () => app(ApprovalDecisionAuthorization::class)
        ->authorizeDecision($this->approval->fresh(), $this->approver))
        ->toThrow(
            WorkflowException::class,
            'This approval step is not currently available.',
        );
});

it('requires a unique signer for every SOP document approval step', function (): void {
    $this->approval->update([
        'approved_by' => $this->approver->id,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
        'approved_at' => now(),
    ]);
    $secondStep = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow->id,
        'step_no' => 2,
        'role_id' => $this->approverRole->id,
        'department_id' => $this->department->id,
        'is_mandatory' => true,
    ]);
    $secondApproval = SopApproval::factory()->create([
        'document_id' => $this->document->id,
        'workflow_step_id' => $secondStep->id,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
    ]);

    expect(fn () => app(ApprovalDecisionAuthorization::class)
        ->authorizeDecision($secondApproval, $this->approver))
        ->toThrow(
            WorkflowException::class,
            'Every SOP approval step must be decided by a different user.',
        );
});

it('keeps an approved document awaiting its remaining mandatory approval', function (): void {
    $remainingStep = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow->id,
        'step_no' => 2,
        'role_id' => $this->approverRole->id,
        'department_id' => $this->department->id,
        'is_mandatory' => true,
    ]);
    SopApproval::factory()->create([
        'document_id' => $this->document->id,
        'workflow_step_id' => $remainingStep->id,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
    ]);
    $this->approval->update([
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
    ]);

    app(ApprovalDecisionOutcome::class)->applyOutcome(
        $this->approval,
        ApprovalDecision::APPROVED,
        $this->approver,
    );

    $auditLog = SopAuditLog::query()
        ->where('document_id', $this->document->id)
        ->where('action', SopAuditLog::ACTION_APPROVED)
        ->firstOrFail();

    expect($this->document->refresh()->documentStatus?->code)->toBe(DocumentStatus::APPROVED)
        ->and($auditLog->new_values)->toMatchArray([
            'approval_id' => $this->approval->id,
            'approved_by' => $this->approver->id,
        ]);
});

it('activates the document after its final mandatory approval', function (): void {
    $this->approval->update([
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::APPROVED),
    ]);

    app(ApprovalDecisionOutcome::class)->applyOutcome(
        $this->approval,
        ApprovalDecision::APPROVED,
        $this->approver,
    );

    expect($this->document->refresh()->documentStatus?->code)->toBe(DocumentStatus::EFFECTIVE)
        ->and(SopAuditLog::query()
            ->where('document_id', $this->document->id)
            ->where('action', SopAuditLog::ACTION_APPROVED)
            ->exists())->toBeTrue();
});

it('retains the existing DMS action return type for Filament callers', function (): void {
    request()->server->set('REMOTE_ADDR', '203.0.113.42');
    request()->headers->set('User-Agent', 'DocuPharma Signature Test');

    $result = app(ApproveDocumentAction::class)->execute(
        $this->approval,
        $this->approver,
        'Approved through the DMS action.',
    );
    $result->refresh();
    $expectedSignatureHash = app(ElectronicSignatureHasher::class)->hashFor(
        recordKey: $result->id,
        meaning: ApprovalDecision::APPROVED,
        signerId: $this->approver->id,
        signedAt: $result->approved_at,
        reason: 'Approved through the DMS action.',
        ipAddress: '203.0.113.42',
        userAgent: 'DocuPharma Signature Test',
    );

    expect($result)->toBe($this->approval)
        ->and($result)->toBeInstanceOf(SopApproval::class)
        ->and($result->approvalDecision?->code)->toBe(ApprovalDecision::APPROVED)
        ->and($result->signatureHash())->toBe($expectedSignatureHash)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($result))->toBeTrue()
        ->and($result->signatureIpAddress())->toBe('203.0.113.42')
        ->and($result->signatureUserAgent())->toBe('DocuPharma Signature Test')
        ->and($this->document->refresh()->documentStatus?->code)->toBe(DocumentStatus::EFFECTIVE);
});

it('canonically signs rejected and returned decisions through existing DMS actions', function (
    string $actionClass,
    string $decisionCode,
    string $expectedStatus,
): void {
    request()->server->set('REMOTE_ADDR', '203.0.113.43');
    request()->headers->set('User-Agent', 'DocuPharma Terminal Signature Test');
    $reason = "Decision recorded as {$decisionCode}.";

    $result = app($actionClass)->execute($this->approval, $this->approver, $reason);
    $result->refresh();
    $expectedSignatureHash = app(ElectronicSignatureHasher::class)->hashFor(
        recordKey: $result->id,
        meaning: $decisionCode,
        signerId: $this->approver->id,
        signedAt: $result->approved_at,
        reason: $reason,
        ipAddress: '203.0.113.43',
        userAgent: 'DocuPharma Terminal Signature Test',
    );

    expect($result)->toBe($this->approval)
        ->and($result)->toBeInstanceOf(SopApproval::class)
        ->and($result->approvalDecision?->code)->toBe($decisionCode)
        ->and($result->signatureMeaning())->toBe($decisionCode)
        ->and($result->signatureSignerId())->toBe($this->approver->id)
        ->and($result->signatureReason())->toBe($reason)
        ->and($result->signatureHash())->toBe($expectedSignatureHash)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($result))->toBeTrue()
        ->and($result->signatureIpAddress())->toBe('203.0.113.43')
        ->and($result->signatureUserAgent())->toBe('DocuPharma Terminal Signature Test')
        ->and($this->document->refresh()->documentStatus?->code)->toBe($expectedStatus);
})->with([
    'rejected' => [
        RejectDocumentAction::class,
        ApprovalDecision::REJECTED,
        DocumentStatus::REJECTED,
    ],
    'returned' => [
        ReturnDocumentAction::class,
        ApprovalDecision::RETURNED,
        DocumentStatus::DRAFT,
    ],
]);

it('preserves terminal decision status, unlocking, and audit payloads', function (
    string $decisionCode,
    string $expectedStatus,
    string $auditAction,
    string $payloadKey,
    int|string $payloadValue,
): void {
    $this->document->update([
        'locked_by' => $this->creator->id,
        'locked_at' => now(),
    ]);
    $this->approval->update([
        'approval_decision_id' => ApprovalDecision::idFor($decisionCode),
    ]);

    app(ApprovalDecisionOutcome::class)->applyOutcome(
        $this->approval,
        $decisionCode,
        $this->approver,
    );

    $auditLog = SopAuditLog::query()
        ->where('document_id', $this->document->id)
        ->where('action', $auditAction)
        ->firstOrFail();

    expect($this->document->refresh()->documentStatus?->code)->toBe($expectedStatus)
        ->and($this->document->locked_by)->toBeNull()
        ->and($this->document->locked_at)->toBeNull()
        ->and($auditLog->new_values)->toMatchArray([
            'approval_id' => $this->approval->id,
            $payloadKey => $payloadValue === 'approver'
                ? $this->approver->id
                : $payloadValue,
        ]);
})->with([
    'rejected' => [
        ApprovalDecision::REJECTED,
        DocumentStatus::REJECTED,
        SopAuditLog::ACTION_REJECTED,
        'decision',
        ApprovalDecision::REJECTED,
    ],
    'returned' => [
        ApprovalDecision::RETURNED,
        DocumentStatus::DRAFT,
        SopAuditLog::ACTION_RETURNED,
        'returned_by',
        'approver',
    ],
]);

it('rejects non-SOP approval instances at the DMS outcome adapter', function (): void {
    $approval = Mockery::mock(ApprovalInstance::class);

    expect(fn () => app(ApprovalDecisionOutcome::class)
        ->applyOutcome($approval, ApprovalDecision::APPROVED, $this->approver))
        ->toThrow(
            InvalidArgumentException::class,
            'The SOP approval outcome adapter requires a SopApproval instance.',
        );
});

it('rolls back decision persistence when outcome handling fails', function (): void {
    $outcome = Mockery::mock(ApprovalDecisionOutcome::class);
    $outcome->shouldReceive('applyOutcome')
        ->once()
        ->andThrow(new RuntimeException('Outcome failed.'));
    app()->instance(ApprovalDecisionOutcome::class, $outcome);

    expect(fn () => app(WorkflowEngineService::class)
        ->approve($this->approval, $this->approver))
        ->toThrow(RuntimeException::class, 'Outcome failed.');

    expect($this->approval->refresh()->approvalDecision?->code)->toBe(ApprovalDecision::PENDING)
        ->and($this->approval->approved_by)->toBeNull()
        ->and($this->approval->approved_at)->toBeNull()
        ->and($this->approval->signature_hash)->toBeNull()
        ->and($this->approval->signature_ip_address)->toBeNull()
        ->and($this->approval->signature_user_agent)->toBeNull()
        ->and($this->document->refresh()->documentStatus?->code)->toBe(DocumentStatus::UNDER_REVIEW);
});
