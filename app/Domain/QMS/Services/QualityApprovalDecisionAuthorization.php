<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
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

        if (! $subject instanceof Deviation || $subject->status !== DeviationStatus::Open) {
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

        if (! $user->can('Investigate:Deviation')) {
            throw new WorkflowException(message: 'You do not have permission to apply the deviation outcome.');
        }

        if (! $user->hasRole($approval->workflowStep->role)) {
            throw new WorkflowException(
                message: "Only users with the '{$approval->workflowStep->role->name}' role can decide this step.",
            );
        }

        if (! $user->can('Manage:Deviation') && $subject->reported_by === $user->id) {
            throw new WorkflowException(message: 'The deviation reporter cannot approve their own submission.');
        }

        $requiredDepartmentId = $approval->workflowStep->resolveRequiredDepartmentId(
            $subject->department_id,
        );

        if (
            ! $user->can('Manage:Deviation')
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
        Deviation $subject,
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
}
