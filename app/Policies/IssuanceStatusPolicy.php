<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IssuanceStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class IssuanceStatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IssuanceStatus');
    }

    public function view(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('View:IssuanceStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IssuanceStatus');
    }

    public function update(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Update:IssuanceStatus');
    }

    public function delete(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Delete:IssuanceStatus');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:IssuanceStatus');
    }

    public function restore(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Restore:IssuanceStatus');
    }

    public function forceDelete(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('ForceDelete:IssuanceStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IssuanceStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IssuanceStatus');
    }

    public function replicate(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Replicate:IssuanceStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IssuanceStatus');
    }

    public function approve(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Approve:IssuanceStatus');
    }

    public function submit(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Submit:IssuanceStatus');
    }

    public function review(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Review:IssuanceStatus');
    }

    public function publish(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Publish:IssuanceStatus');
    }

    public function unpublish(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Unpublish:IssuanceStatus');
    }

    public function revise(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Revise:IssuanceStatus');
    }

    public function archive(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Archive:IssuanceStatus');
    }

    public function unarchive(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Unarchive:IssuanceStatus');
    }

    public function markObsolete(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('MarkObsolete:IssuanceStatus');
    }

    public function completeRetention(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('CompleteRetention:IssuanceStatus');
    }

    public function destroy(AuthUser $authUser, IssuanceStatus $issuanceStatus): bool
    {
        return $authUser->can('Destroy:IssuanceStatus');
    }
}
