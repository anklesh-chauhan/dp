<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Services\AuditLogService;
use App\Models\ApprovalDecision;
use App\Models\DocumentStatus;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\User;
use InvalidArgumentException;

class SopApprovalDecisionOutcomeAdapter implements ApprovalDecisionOutcome
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function applyOutcome(
        ApprovalInstance $approval,
        string $decisionCode,
        User $decidedBy,
    ): ApprovalInstance {
        if (! $approval instanceof SopApproval) {
            throw new InvalidArgumentException(
                'The SOP approval outcome adapter requires a SopApproval instance.'
            );
        }

        return match ($decisionCode) {
            ApprovalDecision::APPROVED => $this->applyApprovedOutcome($approval, $decidedBy),
            ApprovalDecision::REJECTED => $this->applyTerminalOutcome(
                $approval,
                $decidedBy,
                DocumentStatus::REJECTED,
                SopAuditLog::ACTION_REJECTED,
                ApprovalDecision::REJECTED,
            ),
            ApprovalDecision::RETURNED => $this->applyTerminalOutcome(
                $approval,
                $decidedBy,
                DocumentStatus::DRAFT,
                SopAuditLog::ACTION_RETURNED,
                ApprovalDecision::RETURNED,
            ),
            default => throw new InvalidArgumentException(
                "The SOP approval outcome adapter does not support the '{$decisionCode}' decision."
            ),
        };
    }

    private function applyApprovedOutcome(SopApproval $approval, User $decidedBy): SopApproval
    {
        $document = $approval->document()
            ->with(['approvals.workflowStep', 'approvals.approvalDecision'])
            ->firstOrFail();
        $mandatoryApprovals = $document->approvals
            ->filter(fn (SopApproval $item): bool => $item->workflowStep->is_mandatory);

        if ($mandatoryApprovals->every(
            fn (SopApproval $item): bool => $item->approvalDecision?->hasCode(ApprovalDecision::APPROVED)
        )) {
            $document->update([
                'document_status_id' => DocumentStatus::idFor(DocumentStatus::APPROVED),
            ]);
        }
        // Intermediate step approvals leave the document under review until every
        // mandatory workflow step has been signed. Final approval sets Approved;
        // required training and Document Control release happen before Effective.

        $this->auditLogService->log(
            action: SopAuditLog::ACTION_APPROVED,
            newValues: [
                'approval_id' => $approval->id,
                'approved_by' => $decidedBy->id,
                'step_type' => $approval->workflowStep->approvalStepType?->code,
            ],
            userId: $decidedBy->id,
            document: $document,
        );

        return $approval;
    }

    private function applyTerminalOutcome(
        SopApproval $approval,
        User $decidedBy,
        string $documentStatusCode,
        string $auditAction,
        string $decisionCode,
    ): SopApproval {
        $document = $approval->document;
        $document->update([
            'document_status_id' => DocumentStatus::idFor($documentStatusCode),
            'locked_by' => null,
            'locked_at' => null,
        ]);

        $actorKey = $documentStatusCode === DocumentStatus::DRAFT
            ? 'returned_by'
            : 'decision';
        $actorValue = $documentStatusCode === DocumentStatus::DRAFT
            ? $decidedBy->id
            : $decisionCode;

        $this->auditLogService->log(
            action: $auditAction,
            newValues: [
                'approval_id' => $approval->id,
                $actorKey => $actorValue,
            ],
            userId: $decidedBy->id,
            document: $document,
        );

        return $approval;
    }
}
