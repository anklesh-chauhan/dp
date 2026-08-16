<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\DMS\Services\SopApprovalDecisionAuthorizationAdapter;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Exceptions\WorkflowException;
use App\Models\ControlledDocument;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\SopApproval;
use App\Models\SopRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class WorkflowRecipientFinder
{
    public function __construct(
        private readonly SopApprovalDecisionAuthorizationAdapter $documentAuthorization,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function documentStakeholders(ControlledDocument $document): Collection
    {
        return $this->usersByIds(
            $document->created_by,
            $document->owner_id,
            $document->submitted_by,
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function currentDocumentReviewers(ControlledDocument $document): Collection
    {
        $approval = $this->currentDocumentApproval($document);

        if (! $approval instanceof SopApproval) {
            return collect();
        }

        return $this->usersWithRole($approval->workflowStep->role)
            ->filter(function (User $user) use ($approval): bool {
                try {
                    $this->documentAuthorization->authorizeDecision($approval, $user);

                    return true;
                } catch (WorkflowException) {
                    return false;
                }
            })
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    public function templateStakeholders(DocumentTemplateVersion $version): Collection
    {
        $version->loadMissing('template');

        return $this->usersByIds(
            $version->created_by,
            $version->submitted_by,
            $version->template?->created_by,
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function currentTemplateReviewers(DocumentTemplateVersion $version): Collection
    {
        $instance = $this->currentTemplateApproval($version);

        if (! $instance instanceof DocumentTemplateApprovalInstance) {
            return collect();
        }

        $version->loadMissing('template');
        $excludedIds = collect([
            $version->created_by,
            $version->submitted_by,
            $version->template?->created_by,
        ])->filter()->map(fn (mixed $id): int => (int) $id);

        $requiredDepartmentId = $instance->workflowStep->resolveRequiredDepartmentId(
            $version->approvalSubjectDepartmentId(),
        );

        return $this->usersWithRole($instance->workflowStep->role)
            ->filter(function (User $user) use ($excludedIds, $requiredDepartmentId): bool {
                if ($excludedIds->contains($user->id)) {
                    return false;
                }

                if (! $user->can('Decide:DocumentTemplateApproval')) {
                    return false;
                }

                if (
                    ! $user->hasRole(SopRole::ADMINISTRATOR)
                    && $user->department_id !== null
                    && $requiredDepartmentId !== null
                    && $user->department_id !== $requiredDepartmentId
                ) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    public function currentDocumentApproval(ControlledDocument $document): ?SopApproval
    {
        $document->loadMissing([
            'approvals.approvalDecision',
            'approvals.workflowStep',
            'documentStatus',
        ]);

        return $document->approvals
            ->sortBy(fn (SopApproval $approval): int => (int) $approval->workflowStep->step_no)
            ->first(fn (SopApproval $approval): bool => $approval->isActionable());
    }

    public function currentTemplateApproval(DocumentTemplateVersion $version): ?DocumentTemplateApprovalInstance
    {
        $version->loadMissing([
            'approvalInstances.workflowStep.role',
            'approvalInstances.workflowStep.department',
        ]);

        $latestSubmissionUuid = $version->approvalInstances
            ->sortByDesc('id')
            ->value('submission_uuid');

        if ($latestSubmissionUuid === null) {
            return null;
        }

        $instances = $version->approvalInstances
            ->where('submission_uuid', $latestSubmissionUuid)
            ->sortBy(fn (DocumentTemplateApprovalInstance $instance): int => (int) $instance->workflowStep->step_no)
            ->values();

        return $instances->first(function (DocumentTemplateApprovalInstance $instance) use ($instances): bool {
            if ($instance->decision_code !== ApprovalDecisionCode::PENDING->value) {
                return false;
            }

            return ! $instances
                ->filter(fn (DocumentTemplateApprovalInstance $item): bool => $item->workflowStep->step_no < $instance->workflowStep->step_no
                    && $item->workflowStep->is_mandatory)
                ->contains(fn (DocumentTemplateApprovalInstance $item): bool => $item->decision_code !== ApprovalDecisionCode::APPROVED->value);
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
