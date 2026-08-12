<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SopRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class SopRolePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SopRole');
    }

    public function view(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('View:SopRole');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SopRole');
    }

    public function update(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Update:SopRole');
    }

    public function delete(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Delete:SopRole');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SopRole');
    }

    public function restore(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Restore:SopRole');
    }

    public function forceDelete(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('ForceDelete:SopRole');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SopRole');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SopRole');
    }

    public function replicate(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Replicate:SopRole');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SopRole');
    }

    public function approve(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Approve:SopRole');
    }

    public function submit(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Submit:SopRole');
    }

    public function review(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Review:SopRole');
    }

    public function publish(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Publish:SopRole');
    }

    public function unpublish(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Unpublish:SopRole');
    }

    public function revise(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Revise:SopRole');
    }

    public function archive(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Archive:SopRole');
    }

    public function unarchive(AuthUser $authUser, SopRole $sopRole): bool
    {
        return $authUser->can('Unarchive:SopRole');
    }

}