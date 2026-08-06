<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Services\AuditLogService;
use App\Exceptions\WorkflowException;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopAuditLog;
use App\Models\User;
use InvalidArgumentException;

class SopApprovalSubmissionLifecycleAdapter implements ApprovalSubmissionLifecycle
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly DocumentLockService $documentLockService,
    ) {}

    public function assertSubmittable(ApprovableSubject $subject): void
    {
        $document = $this->sopDocument($subject);

        if (! $document->documentStatus?->hasCode(DocumentStatus::DRAFT)) {
            throw new WorkflowException(
                message: 'Only draft documents can be submitted for approval.'
            );
        }

        if ($this->requiresCompletedExecution($document)) {
            $document->loadMissing('sections');

            if ($document->sections->isEmpty()
                || $document->sections->contains(fn ($section): bool => ! $section->isCompleted())
                || $document->sections->contains(fn ($section): bool => ! $section->isIndependentlyVerified())
                || $document->sections->contains(fn ($section): bool => ! $section->hasValidStructuredConfiguration())
                || $document->sections->contains(function ($section): bool {
                    $section->loadMissing('items');

                    return $section->items->contains(fn ($item): bool => ! $item->responseIsValidFor((string) $section->section_type)
                        || ($item->is_required && ! $item->isIndependentlyVerified()));
                })) {
                throw new WorkflowException(
                    message: 'Complete and independently verify every execution section before submitting this controlled record for approval.'
                );
            }
        }
    }

    private function requiresCompletedExecution(ControlledDocument $document): bool
    {
        return in_array($document->documentType?->code, [DocumentType::BATCH_RECORD, 'BPR', 'CHECKLIST'], true);
    }

    public function prepareSubmission(ApprovableSubject $subject, User $submitter): void
    {
        $document = $this->sopDocument($subject);

        if ($document->isLocked()) {
            $this->documentLockService->unlockDocument($document, $submitter, force: true);
        }
    }

    public function markSubmitted(
        ApprovableSubject $subject,
        ApprovalWorkflowDefinition $workflow,
        User $submitter,
    ): void {
        $document = $this->sopDocument($subject);

        $document->update([
            'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        ]);

        $this->auditLogService->log(
            action: SopAuditLog::ACTION_SUBMITTED,
            newValues: [
                'workflow_id' => $workflow->approvalWorkflowDefinitionKey(),
                'submitted_by' => $submitter->id,
            ],
            userId: $submitter->id,
            document: $document,
        );
    }

    private function sopDocument(ApprovableSubject $subject): ControlledDocument
    {
        if (! $subject instanceof ControlledDocument) {
            throw new InvalidArgumentException(
                'The SOP approval submission lifecycle adapter requires a ControlledDocument subject.'
            );
        }

        return $subject;
    }
}
