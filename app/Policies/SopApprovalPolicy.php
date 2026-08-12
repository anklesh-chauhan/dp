<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SopApproval;
use Illuminate\Auth\Access\HandlesAuthorization;

class SopApprovalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SopApproval');
    }

    public function view(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('View:SopApproval');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SopApproval');
    }

    public function update(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Update:SopApproval');
    }

    public function delete(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Delete:SopApproval');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SopApproval');
    }

    public function restore(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Restore:SopApproval');
    }

    public function forceDelete(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('ForceDelete:SopApproval');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SopApproval');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SopApproval');
    }

    public function replicate(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Replicate:SopApproval');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SopApproval');
    }

    public function approve(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Approve:SopApproval');
    }

    public function submit(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Submit:SopApproval');
    }

    public function review(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Review:SopApproval');
    }

    public function publish(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Publish:SopApproval');
    }

    public function unpublish(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Unpublish:SopApproval');
    }

    public function revise(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Revise:SopApproval');
    }

    public function archive(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Archive:SopApproval');
    }

    public function unarchive(AuthUser $authUser, SopApproval $sopApproval): bool
    {
        return $authUser->can('Unarchive:SopApproval');
    }

}