<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\DocumentExecution;
use App\Models\DocumentIssuance;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DocumentIssuanceAccessService
{
    public function canManageLifecycle(User $user): bool
    {
        return $user->can('Issue:DocumentIssuance')
            || $user->can('Recall:DocumentIssuance')
            || $user->can('Destroy:DocumentIssuance');
    }

    public function isRecipient(User $user, DocumentIssuance $issuance): bool
    {
        if ($issuance->issued_to_user_id !== null && (int) $issuance->issued_to_user_id === (int) $user->id) {
            return true;
        }

        return $issuance->issued_to_department_id !== null
            && $user->department_id !== null
            && (int) $issuance->issued_to_department_id === (int) $user->department_id;
    }

    public function hasRecipientAssignment(DocumentIssuance $issuance): bool
    {
        return $issuance->issued_to_user_id !== null || $issuance->issued_to_department_id !== null;
    }

    public function canAccess(User $user, DocumentIssuance $issuance): bool
    {
        if ($this->canManageLifecycle($user)) {
            return true;
        }

        if (! $this->hasRecipientAssignment($issuance)) {
            return true;
        }

        if ($this->isRecipient($user, $issuance)) {
            return true;
        }

        return $this->hasExecutionStakeholderAccess($user, $issuance);
    }

    /**
     * @param  Builder<DocumentIssuance>  $query
     * @return Builder<DocumentIssuance>
     */
    public function constrainVisibleToUser(Builder $query, User $user): Builder
    {
        if ($this->canManageLifecycle($user)) {
            return $query;
        }

        return $query->where(function (Builder $accessQuery) use ($user): void {
            $this->constrainRecipientAssignment($accessQuery, $user);
            $this->constrainIssuanceStakeholderAccess($accessQuery, $user);
        });
    }

    /**
     * @param  Builder<DocumentExecution>  $query
     * @return Builder<DocumentExecution>
     */
    public function constrainExecutionsVisibleToUser(Builder $query, User $user): Builder
    {
        if ($this->canManageLifecycle($user)) {
            return $query;
        }

        return $query->where(function (Builder $accessQuery) use ($user): void {
            $accessQuery->whereHas(
                'issuance',
                fn (Builder $issuanceQuery): Builder => $this->constrainRecipientAssignment($issuanceQuery, $user),
            );

            $accessQuery->orWhere(
                fn (Builder $stakeholderQuery): Builder => $this->applyExecutionStakeholderConditions($stakeholderQuery, $user),
            );
        });
    }

    /**
     * @param  Builder<DocumentIssuance>  $query
     * @return Builder<DocumentIssuance>
     */
    private function constrainRecipientAssignment(Builder $query, User $user): Builder
    {
        $query
            ->where(function (Builder $unrestrictedQuery): void {
                $unrestrictedQuery
                    ->whereNull('issued_to_user_id')
                    ->whereNull('issued_to_department_id');
            })
            ->orWhere('issued_to_user_id', $user->id);

        if ($user->department_id !== null) {
            $query->orWhere('issued_to_department_id', $user->department_id);
        }

        return $query;
    }

    /**
     * @param  Builder<DocumentIssuance>  $query
     */
    private function constrainIssuanceStakeholderAccess(Builder $query, User $user): void
    {
        $query->orWhereHas(
            'execution',
            fn (Builder $executionQuery): Builder => $this->applyExecutionStakeholderConditions($executionQuery, $user),
        );
    }

    /**
     * @param  Builder<DocumentExecution>  $query
     * @return Builder<DocumentExecution>
     */
    private function applyExecutionStakeholderConditions(Builder $query, User $user): Builder
    {
        $query->where('supervisor_id', $user->id);

        if ($user->can('Review:DocumentExecution')) {
            $query->orWhere(function (Builder $reviewQuery) use ($user): void {
                $reviewQuery
                    ->where('status', DocumentExecution::STATUS_UNDER_REVIEW)
                    ->where(function (Builder $supervisorQuery) use ($user): void {
                        $supervisorQuery
                            ->whereNull('supervisor_id')
                            ->orWhere('supervisor_id', $user->id);
                    });
            });
        }

        if ($user->can('Approve:DocumentExecution')) {
            $query->orWhere('status', DocumentExecution::STATUS_QA_REVIEW);
        }

        return $query;
    }

    private function hasExecutionStakeholderAccess(User $user, DocumentIssuance $issuance): bool
    {
        $execution = $issuance->relationLoaded('execution')
            ? $issuance->execution
            : $issuance->execution()->first();

        if ($execution === null) {
            return false;
        }

        if ($execution->supervisor_id !== null && (int) $execution->supervisor_id === (int) $user->id) {
            return true;
        }

        if ($execution->status === DocumentExecution::STATUS_UNDER_REVIEW && $user->can('Review:DocumentExecution')) {
            return $execution->supervisor_id === null || (int) $execution->supervisor_id === (int) $user->id;
        }

        if ($execution->status === DocumentExecution::STATUS_QA_REVIEW && $user->can('Approve:DocumentExecution')) {
            return true;
        }

        return false;
    }
}
