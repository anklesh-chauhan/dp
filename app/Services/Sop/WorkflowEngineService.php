<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Domain\DMS\Services\DocumentActivationService;
use App\Domain\DMS\Services\DocumentLockService;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Domain\Shared\Services\AuditLogService;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalDecision;
use App\Models\DocumentStatus;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkflowEngineService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly DocumentLockService $documentLockService,
        private readonly DocumentActivationService $documentActivationService,
        private readonly ApprovalWorkflowDefinitionSelector $workflowDefinitionSelector,
        private readonly ApprovalInstancePersistence $approvalInstancePersistence,
    ) {}

    public function start(SopDocument $document, User $submitter, ?ApprovalWorkflowDefinition $workflow = null): void
    {
        if (! $document->documentStatus?->hasCode(DocumentStatus::DRAFT)) {
            throw new WorkflowException(
                message: 'Only draft documents can be submitted for approval.'
            );
        }

        if (! $this->canSubmit($document, $submitter)) {
            throw new WorkflowException(
                message: 'You are not authorized to submit this document for approval.'
            );
        }

        $workflow ??= $this->resolveWorkflow($document);

        if ($workflow === null) {
            throw new WorkflowException(
                message: 'No active approval workflow is configured for this department.'
            );
        }

        $underReviewStatusId = DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW);

        DB::transaction(function () use ($document, $workflow, $submitter, $underReviewStatusId): void {
            if ($document->isLocked()) {
                $this->documentLockService->unlockDocument($document, $submitter, force: true);
            }

            $this->approvalInstancePersistence->initializeFor($document, $workflow);

            $document->update(['document_status_id' => $underReviewStatusId]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_SUBMITTED,
                newValues: [
                    'workflow_id' => $workflow->approvalWorkflowDefinitionKey(),
                    'submitted_by' => $submitter->id,
                ],
                userId: $submitter->id,
                document: $document,
            );
        });
    }

    public function approve(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        if (! $approval->canBeApprovedBy($approver)) {
            throw new WorkflowException(
                message: 'This approval step is not available for you yet.'
            );
        }

        $approvedDecisionId = ApprovalDecision::idFor(ApprovalDecision::APPROVED);
        $approvedStatusId = DocumentStatus::idFor(DocumentStatus::APPROVED);

        return DB::transaction(function () use ($approval, $approver, $comments, $approvedDecisionId, $approvedStatusId): SopApproval {
            $approval->update([
                'approved_by' => $approver->id,
                'approval_decision_id' => $approvedDecisionId,
                'comments' => $comments,
                'approved_at' => now(),
                'signature_hash' => hash('sha256', $approval->id.'|'.$approver->id.'|'.now()->toISOString()),
            ]);

            $document = $approval->document()->with(['approvals.workflowStep', 'approvals.approvalDecision'])->firstOrFail();
            $mandatoryApprovals = $document->approvals->filter(fn (SopApproval $item): bool => $item->workflowStep->is_mandatory);

            if ($mandatoryApprovals->every(fn (SopApproval $item): bool => $item->approvalDecision?->hasCode(ApprovalDecision::APPROVED))) {
                $this->documentActivationService->activate($document, $approver);
            } else {
                $document->update(['document_status_id' => $approvedStatusId]);
            }

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_APPROVED,
                newValues: [
                    'approval_id' => $approval->id,
                    'approved_by' => $approver->id,
                    'step_type' => $approval->workflowStep->approvalStepType?->code,
                ],
                userId: $approver->id,
                document: $document,
            );

            return $approval;
        });
    }

    public function reject(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        if (! $approval->canBeApprovedBy($approver)) {
            throw new WorkflowException(
                message: 'This approval step is not available for you yet.'
            );
        }

        return $this->decide(
            $approval,
            $approver,
            ApprovalDecision::idFor(ApprovalDecision::REJECTED),
            DocumentStatus::idFor(DocumentStatus::REJECTED),
            SopAuditLog::ACTION_REJECTED,
            $comments,
        );
    }

    public function return(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        if (! $approval->canBeApprovedBy($approver)) {
            throw new WorkflowException(
                message: 'This approval step is not available for you yet.'
            );
        }

        return DB::transaction(function () use ($approval, $approver, $comments): SopApproval {
            $approval->update([
                'approved_by' => $approver->id,
                'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::RETURNED),
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            $document = $approval->document;
            $document->update([
                'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_RETURNED,
                newValues: [
                    'approval_id' => $approval->id,
                    'returned_by' => $approver->id,
                ],
                userId: $approver->id,
                document: $document,
            );

            return $approval;
        });
    }

    public function resolveWorkflow(ApprovableSubject $subject): ?ApprovalWorkflowDefinition
    {
        return $this->workflowDefinitionSelector->selectFor($subject);
    }

    public function canSubmit(ApprovableSubject $subject, User $user): bool
    {
        if (! $user->can('Submit:SopDocument') && ! $user->can('Update:SopDocument')) {
            return false;
        }

        if ($user->hasRole(SopRole::ADMINISTRATOR)) {
            return true;
        }

        if ($user->hasRole(SopRole::MAKER)) {
            if ($user->department_id !== null && $user->department_id !== $subject->approvalSubjectDepartmentId()) {
                return false;
            }

            return true;
        }

        return $subject->approvalSubjectCreatedById() === $user->id
            || $subject->approvalSubjectOwnerId() === $user->id;
    }

    private function decide(SopApproval $approval, User $approver, int $decisionId, int $documentStatusId, string $auditAction, ?string $comments): SopApproval
    {
        return DB::transaction(function () use ($approval, $approver, $decisionId, $documentStatusId, $auditAction, $comments): SopApproval {
            $approval->update([
                'approved_by' => $approver->id,
                'approval_decision_id' => $decisionId,
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            $document = $approval->document;
            $document->update([
                'document_status_id' => $documentStatusId,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $this->auditLogService->log(
                action: $auditAction,
                newValues: [
                    'approval_id' => $approval->id,
                    'decision' => ApprovalDecision::query()->whereKey($decisionId)->value('code'),
                ],
                userId: $approver->id,
                document: $document,
            );

            return $approval;
        });
    }
}
