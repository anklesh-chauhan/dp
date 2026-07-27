<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ChangeControl;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChangeControlPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChangeControl');
    }

    public function view(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('View:ChangeControl');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChangeControl');
    }

    public function update(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Update:ChangeControl');
    }

    public function delete(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Delete:ChangeControl');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChangeControl');
    }

    public function restore(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Restore:ChangeControl');
    }

    public function forceDelete(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('ForceDelete:ChangeControl');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChangeControl');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChangeControl');
    }

    public function replicate(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Replicate:ChangeControl');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChangeControl');
    }

    public function approve(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Approve:ChangeControl');
    }

    public function submit(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Submit:ChangeControl');
    }

    public function review(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Review:ChangeControl');
    }

    public function publish(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Publish:ChangeControl');
    }

    public function unpublish(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Unpublish:ChangeControl');
    }

    public function revise(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Revise:ChangeControl');
    }

    public function archive(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Archive:ChangeControl');
    }

    public function unarchive(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Unarchive:ChangeControl');
    }

    public function markObsolete(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('MarkObsolete:ChangeControl');
    }

    public function completeRetention(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('CompleteRetention:ChangeControl');
    }

    public function destroy(AuthUser $authUser, ChangeControl $changeControl): bool
    {
        return $authUser->can('Destroy:ChangeControl');
    }
}
