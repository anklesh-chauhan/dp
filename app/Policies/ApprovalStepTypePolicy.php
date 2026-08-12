<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ApprovalStepType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalStepTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ApprovalStepType');
    }

    public function view(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('View:ApprovalStepType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ApprovalStepType');
    }

    public function update(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Update:ApprovalStepType');
    }

    public function delete(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Delete:ApprovalStepType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ApprovalStepType');
    }

    public function restore(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Restore:ApprovalStepType');
    }

    public function forceDelete(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('ForceDelete:ApprovalStepType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ApprovalStepType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ApprovalStepType');
    }

    public function replicate(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Replicate:ApprovalStepType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ApprovalStepType');
    }

    public function approve(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Approve:ApprovalStepType');
    }

    public function submit(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Submit:ApprovalStepType');
    }

    public function review(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Review:ApprovalStepType');
    }

    public function publish(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Publish:ApprovalStepType');
    }

    public function unpublish(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Unpublish:ApprovalStepType');
    }

    public function revise(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Revise:ApprovalStepType');
    }

    public function archive(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Archive:ApprovalStepType');
    }

    public function unarchive(AuthUser $authUser, ApprovalStepType $approvalStepType): bool
    {
        return $authUser->can('Unarchive:ApprovalStepType');
    }

}