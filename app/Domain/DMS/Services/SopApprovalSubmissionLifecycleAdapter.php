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

        if ($document->documentType?->requiresExecutionRecord()) {
            $document->loadMissing('sections.items', 'sections.executionTables.items');

            $validationIssues = $this->masterValidationIssues($document);

            if ($validationIssues !== []) {
                throw new WorkflowException(
                    message: 'Complete the writable document master before approval: '.implode(' ', $validationIssues)
                );
            }
        }
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
            'submitted_by' => $submitter->id,
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

    /** @return list<string> */
    private function masterValidationIssues(ControlledDocument $document): array
    {
        if ($document->sections->isEmpty()) {
            return ['Add at least one master section.'];
        }

        $issues = [];

        foreach ($document->sections as $section) {
            if ($section->requiresFieldDefinitions() && $section->items->isEmpty()) {
                $issues[] = "The '{$section->title}' section needs at least one execution field.";
            }

            foreach ($section->executionTables->filter(fn ($table): bool => $table->items->isEmpty()) as $table) {
                $issues[] = "The '{$table->title}' table in '{$section->title}' needs at least one column.";
            }
        }

        return $issues;
    }
}
