<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

final class QualityWorkflowRecipientFinder
{
    public function __construct(
        private readonly QualityApprovalDecisionAuthorization $qualityAuthorization,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function deviationStakeholders(Deviation $deviation): Collection
    {
        return $this->usersByIds($deviation->reported_by, $deviation->owner_id);
    }

    /**
     * @return Collection<int, User>
     */
    public function currentDeviationReviewers(Deviation $deviation): Collection
    {
        $instance = $this->currentQualityApproval($deviation);

        if (! $instance instanceof QualityApprovalInstance) {
            return collect();
        }

        return $this->usersWithRole($instance->workflowStep->role)
            ->filter(fn (User $user): bool => $this->qualityAuthorization->canDecide($instance, $user))
            ->values();
    }

    public function currentQualityApproval(Deviation $deviation): ?QualityApprovalInstance
    {
        $instances = QualityApprovalInstance::query()
            ->whereMorphedTo('subject', $deviation)
            ->with(['workflowStep.role', 'workflowStep.department', 'subject'])
            ->orderBy('id')
            ->get();

        $latestSubmissionUuid = $instances->sortByDesc('id')->value('submission_uuid');

        if ($latestSubmissionUuid === null) {
            return null;
        }

        $cycle = $instances
            ->where('submission_uuid', $latestSubmissionUuid)
            ->sortBy(fn (QualityApprovalInstance $instance): int => (int) $instance->workflowStep->step_no)
            ->values();

        return $cycle->first(function (QualityApprovalInstance $instance) use ($cycle): bool {
            if ($instance->decision_code !== ApprovalDecisionCode::PENDING->value) {
                return false;
            }

            return ! $cycle
                ->filter(fn (QualityApprovalInstance $item): bool => $item->workflowStep->step_no < $instance->workflowStep->step_no
                    && $item->workflowStep->is_mandatory)
                ->contains(fn (QualityApprovalInstance $item): bool => $item->decision_code !== ApprovalDecisionCode::APPROVED->value);
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function usersWithRole(?Role $role): Collection
    {
        if ($role === null || blank($role->name)) {
            return collect();
        }

        return User::role($role->name)->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function usersByIds(int|string|null ...$ids): Collection
    {
        $userIds = collect($ids)
            ->filter(fn (int|string|null $id): bool => $id !== null && $id !== '')
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $userIds)->get();
    }
}
