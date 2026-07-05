<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Update:Role');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Delete:Role');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Role');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Restore:Role');
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('ForceDelete:Role');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Role');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Replicate:Role');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Role');
    }

    public function approve(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Approve:Role');
    }

    public function submit(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Submit:Role');
    }

    public function review(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Review:Role');
    }

    public function publish(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Publish:Role');
    }

    public function unpublish(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Unpublish:Role');
    }

    public function archive(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Archive:Role');
    }

    public function unarchive(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Unarchive:Role');
    }

    public function markObsolete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('MarkObsolete:Role');
    }

    public function completeRetention(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('CompleteRetention:Role');
    }

    public function destroy(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Destroy:Role');
    }

}