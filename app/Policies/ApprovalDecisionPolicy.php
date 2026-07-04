<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ApprovalDecision;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ApprovalDecisionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ApprovalDecision');
    }

    public function view(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('View:ApprovalDecision');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ApprovalDecision');
    }

    public function update(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Update:ApprovalDecision');
    }

    public function delete(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Delete:ApprovalDecision');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ApprovalDecision');
    }

    public function restore(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Restore:ApprovalDecision');
    }

    public function forceDelete(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('ForceDelete:ApprovalDecision');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ApprovalDecision');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ApprovalDecision');
    }

    public function replicate(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Replicate:ApprovalDecision');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ApprovalDecision');
    }

    public function approve(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Approve:ApprovalDecision');
    }

    public function submit(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Submit:ApprovalDecision');
    }

    public function review(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Review:ApprovalDecision');
    }

    public function publish(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Publish:ApprovalDecision');
    }

    public function unpublish(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Unpublish:ApprovalDecision');
    }

    public function archive(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Archive:ApprovalDecision');
    }

    public function unarchive(AuthUser $authUser, ApprovalDecision $approvalDecision): bool
    {
        return $authUser->can('Unarchive:ApprovalDecision');
    }
}
