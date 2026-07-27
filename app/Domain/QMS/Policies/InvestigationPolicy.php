<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\Investigation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InvestigationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Investigation');
    }

    public function view(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('View:Investigation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Investigation');
    }

    public function update(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Update:Investigation');
    }

    public function delete(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Delete:Investigation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Investigation');
    }

    public function restore(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Restore:Investigation');
    }

    public function forceDelete(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('ForceDelete:Investigation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Investigation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Investigation');
    }

    public function replicate(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Replicate:Investigation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Investigation');
    }

    public function approve(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Approve:Investigation');
    }

    public function submit(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Submit:Investigation');
    }

    public function review(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Review:Investigation');
    }

    public function publish(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Publish:Investigation');
    }

    public function unpublish(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Unpublish:Investigation');
    }

    public function revise(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Revise:Investigation');
    }

    public function archive(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Archive:Investigation');
    }

    public function unarchive(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Unarchive:Investigation');
    }

    public function markObsolete(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('MarkObsolete:Investigation');
    }

    public function completeRetention(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('CompleteRetention:Investigation');
    }

    public function destroy(AuthUser $authUser, Investigation $investigation): bool
    {
        return $authUser->can('Destroy:Investigation');
    }
}
