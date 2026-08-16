<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Services\WorkflowNotificationService;
use App\Exceptions\WorkflowException;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionReviewComment;
use App\Models\DocumentStatus;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ControlledDocumentSectionReviewService
{
    public function __construct(
        private readonly SopApprovalDecisionAuthorizationAdapter $approvalAuthorization,
        private readonly AuditLogService $auditLogService,
        private readonly WorkflowNotificationService $workflowNotifications,
    ) {}

    public function actionableApprovalFor(ControlledDocument $document, User $user): ?SopApproval
    {
        return $document->approvals()
            ->actionableFor($user)
            ->with([
                'approvalDecision',
                'document.approvals.approvalDecision',
                'document.approvals.workflowStep',
                'workflowStep.approvalStepType',
                'workflowStep.department',
                'workflowStep.role',
            ])
            ->get()
            ->sortBy('workflowStep.step_no')
            ->first(function (SopApproval $approval) use ($user): bool {
                try {
                    $this->approvalAuthorization->authorizeDecision($approval, $user);

                    return true;
                } catch (WorkflowException) {
                    return false;
                }
            });
    }

    public function canComment(ControlledDocument $document, User $user): bool
    {
        if (! $document->documentStatus?->hasCode(DocumentStatus::UNDER_REVIEW)) {
            return false;
        }

        return $this->actionableApprovalFor($document, $user) instanceof SopApproval;
    }

    public function canResolve(ControlledDocument $document, User $user): bool
    {
        return $document->canBeEditedBy($user);
    }

    public function addComment(
        ControlledDocumentSection $section,
        User $reviewer,
        string $body,
    ): ControlledDocumentSectionReviewComment {
        $section->loadMissing('document.documentStatus');
        $document = $section->document;

        if (! $document instanceof ControlledDocument) {
            throw new WorkflowException(message: 'This section is not attached to a controlled document.');
        }

        $approval = $this->actionableApprovalFor($document, $reviewer);

        if (! $document->documentStatus?->hasCode(DocumentStatus::UNDER_REVIEW) || ! $approval instanceof SopApproval) {
            throw new WorkflowException(
                message: 'Only the current reviewer can comment on sections while the document is in approval.'
            );
        }

        $body = Str::of($body)->trim()->toString();

        if ($body === '') {
            throw new WorkflowException(message: 'Enter the change you want the maker to make in this section.');
        }

        return DB::transaction(function () use ($section, $document, $reviewer, $body, $approval): ControlledDocumentSectionReviewComment {
            $comment = ControlledDocumentSectionReviewComment::query()->create([
                'document_id' => $document->getKey(),
                'section_id' => $section->getKey(),
                'sop_approval_id' => $approval->getKey(),
                'author_id' => $reviewer->getKey(),
                'body' => $body,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_SECTION_REVIEW_COMMENTED,
                newValues: [
                    'section_id' => $section->getKey(),
                    'section_title' => $section->title,
                    'comment_id' => $comment->getKey(),
                    'body' => $body,
                ],
                userId: $reviewer->getKey(),
                document: $document,
            );

            $this->workflowNotifications->notifySectionCommentAdded($comment, $reviewer);

            return $comment;
        });
    }

    public function resolveComment(
        ControlledDocumentSectionReviewComment $comment,
        User $user,
    ): ControlledDocumentSectionReviewComment {
        $comment->loadMissing(['document.documentStatus', 'section']);
        $document = $comment->document;

        if (! $document instanceof ControlledDocument) {
            throw new WorkflowException(message: 'This comment is not attached to a controlled document.');
        }

        if (! $this->canResolve($document, $user)) {
            throw new WorkflowException(
                message: 'Only the maker can mark section comments as addressed after the document is returned for correction.'
            );
        }

        if ($comment->isResolved()) {
            throw new WorkflowException(message: 'This reviewer comment has already been marked as addressed.');
        }

        return DB::transaction(function () use ($comment, $user, $document): ControlledDocumentSectionReviewComment {
            $comment->forceFill([
                'resolved_at' => now(),
                'resolved_by' => $user->getKey(),
            ])->save();

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_SECTION_REVIEW_COMMENT_RESOLVED,
                newValues: [
                    'section_id' => $comment->section_id,
                    'section_title' => $comment->section?->title,
                    'comment_id' => $comment->getKey(),
                ],
                userId: $user->getKey(),
                document: $document,
            );

            $resolved = $comment->refresh();
            $this->workflowNotifications->notifySectionCommentResolved($resolved, $user);

            return $resolved;
        });
    }
}
