<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Domain\Shared\Support\PendingApprovalStep;
use Illuminate\Support\Collection;

final class CurrentPendingQualityApprovalStepResolver
{
    public function forSubject(ApprovableSubject $subject): ?PendingApprovalStep
    {
        if (! method_exists($subject, 'approvalInstances')) {
            return null;
        }

        /** @var Collection<int, QualityApprovalInstance> $instances */
        $instances = $subject->approvalInstances()
            ->with(['workflowStep.role', 'workflowStep.department'])
            ->get()
            ->sortBy(fn (QualityApprovalInstance $instance): int => (int) $instance->workflowStep->step_no)
            ->values();

        if ($instances->isEmpty()) {
            return null;
        }

        $latestSubmissionUuid = $instances->sortByDesc('id')->value('submission_uuid');

        $cycle = $instances
            ->where('submission_uuid', $latestSubmissionUuid)
            ->values();

        $current = $cycle->first(
            fn (QualityApprovalInstance $instance): bool => $this->isQualityStepActionable($instance, $cycle),
        );

        if (! $current instanceof QualityApprovalInstance) {
            return null;
        }

        $step = $current->workflowStep;

        return new PendingApprovalStep(
            stepNo: (int) $step->step_no,
            roleName: (string) $step->role->name,
            stepTypeName: null,
            departmentName: $step->department?->name,
        );
    }

    /**
     * @param  Collection<int, QualityApprovalInstance>  $instances
     */
    private function isQualityStepActionable(QualityApprovalInstance $instance, Collection $instances): bool
    {
        if ($instance->decision_code !== ApprovalDecisionCode::PENDING->value) {
            return false;
        }

        return ! $instances
            ->filter(fn (QualityApprovalInstance $item): bool => $item->workflowStep->step_no < $instance->workflowStep->step_no
                && $item->workflowStep->is_mandatory)
            ->contains(fn (QualityApprovalInstance $item): bool => $item->decision_code !== ApprovalDecisionCode::APPROVED->value);
    }
}
