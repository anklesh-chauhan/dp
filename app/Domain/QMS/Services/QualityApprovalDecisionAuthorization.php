<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Enums\ProductModule;
use App\Exceptions\WorkflowException;
use App\Models\User;
use App\Support\Modules\ModuleManager;

final class QualityApprovalDecisionAuthorization implements ApprovalDecisionAuthorization
{
    public function __construct(private readonly ModuleManager $moduleManager) {}

    public function authorizeDecision(ApprovalInstance $approval, User $user): void
    {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $approval instanceof QualityApprovalInstance) {
            throw new WorkflowException(message: 'Quality approval authorization requires a quality approval instance.');
        }

        $approval->loadMissing([
            'subject',
            'workflowStep.role',
            'workflowStep.department',
        ]);
        $subject = $approval->approvalInstanceSubject();

        if (! $this->subjectIsReadyForDecision($subject)) {
            throw new WorkflowException(message: 'This quality approval is no longer available.');
        }

        if ($approval->decision_code !== 'pending' || ! $this->belongsToLatestCycle($approval, $subject)) {
            throw new WorkflowException(message: 'This quality approval step is not currently available.');
        }

        if ($this->hasPreviousMandatoryStepPending($approval)) {
            throw new WorkflowException(message: 'A previous mandatory quality approval step is still pending.');
        }

        if (! $user->can('Decide:QualityApproval')) {
            throw new WorkflowException(message: 'You do not have permission to decide quality approvals.');
        }

        if (! $this->userCanApplyOutcome($subject, $user)) {
            throw new WorkflowException(message: 'You do not have permission to apply the quality approval outcome.');
        }

        if (! $user->hasRole($approval->workflowStep->role)) {
            throw new WorkflowException(
                message: "Only users with the '{$approval->workflowStep->role->name}' role can decide this step.",
            );
        }

        if (
            $subject->approvalSubjectCreatedById() === $user->id
        ) {
            throw new WorkflowException(message: 'The submitter cannot approve their own submission.');
        }

        if ($this->hasAlreadyDecidedInCycle($approval, $user)) {
            throw new WorkflowException(message: 'A different signer is required for each quality approval step.');
        }

        $requiredDepartmentId = $approval->workflowStep->resolveRequiredDepartmentId(
            $subject->approvalSubjectDepartmentId(),
        );

        if (
            ! $user->can($this->managePermission($subject))
            && $user->department_id !== null
            && $requiredDepartmentId !== null
            && $requiredDepartmentId !== $user->department_id
        ) {
            throw new WorkflowException(message: 'You can only decide quality approvals for your own department.');
        }
    }

    public function canDecide(QualityApprovalInstance $approval, User $user): bool
    {
        try {
            $this->authorizeDecision($approval, $user);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function belongsToLatestCycle(
        QualityApprovalInstance $approval,
        ApprovableSubject $subject,
    ): bool {
        $latestSubmissionUuid = QualityApprovalInstance::query()
            ->whereMorphedTo('subject', $subject)
            ->latest('id')
            ->value('submission_uuid');

        return $approval->submission_uuid === $latestSubmissionUuid;
    }

    private function hasPreviousMandatoryStepPending(
        QualityApprovalInstance $approval,
    ): bool {
        return QualityApprovalInstance::query()
            ->where('submission_uuid', $approval->submission_uuid)
            ->whereHas('workflowStep', fn ($query) => $query
                ->where('step_no', '<', $approval->workflowStep->step_no)
                ->where('is_mandatory', true))
            ->where('decision_code', '!=', 'approved')
            ->exists();
    }

    private function hasAlreadyDecidedInCycle(
        QualityApprovalInstance $approval,
        User $user,
    ): bool {
        return QualityApprovalInstance::query()
            ->where('submission_uuid', $approval->submission_uuid)
            ->where('decided_by', $user->getKey())
            ->whereIn('decision_code', [
                'approved',
                'rejected',
                'returned',
            ])
            ->exists();
    }

    private function subjectIsReadyForDecision(mixed $subject): bool
    {
        return match (true) {
            $subject instanceof Deviation => $subject->status === DeviationStatus::Open,
            $subject instanceof ChangeControl => in_array($subject->status, [
                ChangeControlStatus::Submitted,
                ChangeControlStatus::UnderReview,
            ], true),
            default => false,
        };
    }

    private function userCanApplyOutcome(mixed $subject, User $user): bool
    {
        return match (true) {
            $subject instanceof Deviation => $user->can('Investigate:Deviation'),
            $subject instanceof ChangeControl => $user->can('Review:ChangeControl')
                || $user->can('Approve:ChangeControl'),
            default => false,
        };
    }

    private function managePermission(mixed $subject): string
    {
        return $subject instanceof ChangeControl
            ? 'Manage:ChangeControl'
            : 'Manage:Deviation';
    }
}
