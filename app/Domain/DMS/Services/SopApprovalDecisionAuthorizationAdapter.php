<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Exceptions\WorkflowException;
use App\Models\ApprovalDecision;
use App\Models\SopApproval;
use App\Models\SopRole;
use App\Models\User;

class SopApprovalDecisionAuthorizationAdapter implements ApprovalDecisionAuthorization
{
    public function authorizeDecision(ApprovalInstance $approval, User $user): void
    {
        if (! $approval instanceof SopApproval) {
            throw new WorkflowException(
                message: 'The SOP approval authorization adapter requires a SopApproval instance.'
            );
        }

        if (! $this->isActionable($approval)) {
            throw new WorkflowException(
                message: 'This approval step is not currently available.'
            );
        }

        $approval->loadMissing([
            'workflowStep.role',
            'workflowStep.department',
            'document',
        ]);

        if (! $user->can('Approve:SopDocument')) {
            throw new WorkflowException(
                message: 'You do not have permission to approve SOP documents.'
            );
        }

        if (! $user->hasRole($approval->workflowStep->role)) {
            throw new WorkflowException(
                message: "Only users with the '{$approval->workflowStep->role->name}' role can approve this step."
            );
        }

        if ($this->violatesSeparationOfDuties($approval, $user)) {
            throw new WorkflowException(
                message: 'You cannot approve this document because of the separation of duties policy.'
            );
        }

        if ($this->hasAlreadyDecidedAnotherStep($approval, $user)) {
            throw new WorkflowException(
                message: 'Every SOP approval step must be decided by a different user.'
            );
        }

        if ($this->violatesDepartmentScope($approval, $user)) {
            throw new WorkflowException(
                message: 'You can only approve documents for your own department.'
            );
        }
    }

    private function isActionable(SopApproval $approval): bool
    {
        if (! $approval->approvalDecision?->hasCode(ApprovalDecision::PENDING)) {
            return false;
        }

        $approval->loadMissing(['document.approvals.workflowStep', 'workflowStep']);

        $previousMandatoryStepsPending = $approval->document->approvals
            ->filter(fn (SopApproval $item): bool => $item->workflowStep->step_no < $approval->workflowStep->step_no
                && $item->workflowStep->is_mandatory)
            ->contains(fn (SopApproval $item): bool => ! $item->approvalDecision?->hasCode(ApprovalDecision::APPROVED));

        return ! $previousMandatoryStepsPending;
    }

    private function violatesSeparationOfDuties(SopApproval $approval, User $user): bool
    {
        if ($user->hasRole(SopRole::ADMINISTRATOR)) {
            return false;
        }

        return $approval->document->created_by === $user->id;
    }

    private function violatesDepartmentScope(SopApproval $approval, User $user): bool
    {
        if ($user->hasRole(SopRole::ADMINISTRATOR)) {
            return false;
        }

        if ($user->department_id === null) {
            return false;
        }

        $requiredDepartmentId = $approval->workflowStep->resolveRequiredDepartmentId(
            $approval->document->department_id
        );

        if ($requiredDepartmentId === null) {
            return false;
        }

        return $requiredDepartmentId !== $user->department_id;
    }

    private function hasAlreadyDecidedAnotherStep(SopApproval $approval, User $user): bool
    {
        return SopApproval::query()
            ->where('document_id', $approval->document_id)
            ->whereKeyNot($approval->id)
            ->where('approved_by', $user->id)
            ->exists();
    }
}
