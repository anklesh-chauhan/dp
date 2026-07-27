<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\Capa;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CapaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Capa');
    }

    public function view(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('View:Capa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Capa');
    }

    public function update(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Update:Capa');
    }

    public function delete(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Delete:Capa');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Capa');
    }

    public function restore(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Restore:Capa');
    }

    public function forceDelete(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('ForceDelete:Capa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Capa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Capa');
    }

    public function replicate(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Replicate:Capa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Capa');
    }

    public function approve(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Approve:Capa');
    }

    public function submit(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Submit:Capa');
    }

    public function review(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Review:Capa');
    }

    public function publish(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Publish:Capa');
    }

    public function unpublish(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Unpublish:Capa');
    }

    public function revise(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Revise:Capa');
    }

    public function archive(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Archive:Capa');
    }

    public function unarchive(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Unarchive:Capa');
    }

    public function markObsolete(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('MarkObsolete:Capa');
    }

    public function completeRetention(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('CompleteRetention:Capa');
    }

    public function destroy(AuthUser $authUser, Capa $capa): bool
    {
        return $authUser->can('Destroy:Capa');
    }
}
