<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:User');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:User');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('Approve:User');
    }

    public function submit(AuthUser $authUser): bool
    {
        return $authUser->can('Submit:User');
    }

    public function review(AuthUser $authUser): bool
    {
        return $authUser->can('Review:User');
    }

    public function publish(AuthUser $authUser): bool
    {
        return $authUser->can('Publish:User');
    }

    public function unpublish(AuthUser $authUser): bool
    {
        return $authUser->can('Unpublish:User');
    }

    public function revise(AuthUser $authUser): bool
    {
        return $authUser->can('Revise:User');
    }

    public function archive(AuthUser $authUser): bool
    {
        return $authUser->can('Archive:User');
    }

    public function unarchive(AuthUser $authUser): bool
    {
        return $authUser->can('Unarchive:User');
    }

    public function markObsolete(AuthUser $authUser): bool
    {
        return $authUser->can('MarkObsolete:User');
    }

    public function completeRetention(AuthUser $authUser): bool
    {
        return $authUser->can('CompleteRetention:User');
    }

    public function destroy(AuthUser $authUser): bool
    {
        return $authUser->can('Destroy:User');
    }
}
