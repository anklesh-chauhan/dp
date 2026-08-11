<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ControlledDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ControlledDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ControlledDocument');
    }

    public function view(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('View:ControlledDocument');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ControlledDocument');
    }

    public function update(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        if (! $authUser->can('Update:ControlledDocument') || ! $authUser instanceof User) {
            return false;
        }

        return $controlledDocument->canBeEditedBy($authUser);
    }

    public function delete(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Delete:ControlledDocument');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ControlledDocument');
    }

    public function restore(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Restore:ControlledDocument');
    }

    public function forceDelete(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('ForceDelete:ControlledDocument');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ControlledDocument');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ControlledDocument');
    }

    public function replicate(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Replicate:ControlledDocument');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ControlledDocument');
    }

    public function approve(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Approve:ControlledDocument');
    }

    public function submit(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Submit:ControlledDocument');
    }

    public function review(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Review:ControlledDocument');
    }

    public function publish(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Publish:ControlledDocument');
    }

    public function unpublish(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Unpublish:ControlledDocument');
    }

    public function revise(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Revise:ControlledDocument');
    }

    public function archive(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Archive:ControlledDocument');
    }

    public function unarchive(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Unarchive:ControlledDocument');
    }

    public function markObsolete(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('MarkObsolete:ControlledDocument');
    }

    public function completeRetention(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('CompleteRetention:ControlledDocument');
    }

    public function destroy(AuthUser $authUser, ControlledDocument $controlledDocument): bool
    {
        return $authUser->can('Destroy:ControlledDocument');
    }
}
