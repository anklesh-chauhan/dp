<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SopTemplateApprovalInstance;
use Illuminate\Auth\Access\HandlesAuthorization;

class SopTemplateApprovalInstancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SopTemplateApprovalInstance');
    }

    public function view(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('View:SopTemplateApprovalInstance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SopTemplateApprovalInstance');
    }

    public function update(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Update:SopTemplateApprovalInstance');
    }

    public function delete(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Delete:SopTemplateApprovalInstance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SopTemplateApprovalInstance');
    }

    public function restore(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Restore:SopTemplateApprovalInstance');
    }

    public function forceDelete(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('ForceDelete:SopTemplateApprovalInstance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SopTemplateApprovalInstance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SopTemplateApprovalInstance');
    }

    public function replicate(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Replicate:SopTemplateApprovalInstance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SopTemplateApprovalInstance');
    }

    public function approve(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Approve:SopTemplateApprovalInstance');
    }

    public function submit(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Submit:SopTemplateApprovalInstance');
    }

    public function review(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Review:SopTemplateApprovalInstance');
    }

    public function publish(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Publish:SopTemplateApprovalInstance');
    }

    public function unpublish(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Unpublish:SopTemplateApprovalInstance');
    }

    public function revise(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Revise:SopTemplateApprovalInstance');
    }

    public function archive(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Archive:SopTemplateApprovalInstance');
    }

    public function unarchive(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Unarchive:SopTemplateApprovalInstance');
    }

    public function markObsolete(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('MarkObsolete:SopTemplateApprovalInstance');
    }

    public function completeRetention(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('CompleteRetention:SopTemplateApprovalInstance');
    }

    public function destroy(AuthUser $authUser, SopTemplateApprovalInstance $sopTemplateApprovalInstance): bool
    {
        return $authUser->can('Destroy:SopTemplateApprovalInstance');
    }

}