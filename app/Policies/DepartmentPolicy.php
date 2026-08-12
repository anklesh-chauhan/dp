<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Department');
    }

    public function view(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('View:Department');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Department');
    }

    public function update(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Update:Department');
    }

    public function delete(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Delete:Department');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Department');
    }

    public function restore(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Restore:Department');
    }

    public function forceDelete(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('ForceDelete:Department');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Department');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Department');
    }

    public function replicate(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Replicate:Department');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Department');
    }

    public function approve(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Approve:Department');
    }

    public function submit(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Submit:Department');
    }

    public function review(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Review:Department');
    }

    public function publish(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Publish:Department');
    }

    public function unpublish(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Unpublish:Department');
    }

    public function revise(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Revise:Department');
    }

    public function archive(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Archive:Department');
    }

    public function unarchive(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Unarchive:Department');
    }

}