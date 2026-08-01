<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DocumentTemplateApprovalInstance;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentTemplateApprovalInstancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentTemplateApprovalInstance');
    }

    public function view(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('View:DocumentTemplateApprovalInstance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentTemplateApprovalInstance');
    }

    public function update(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Update:DocumentTemplateApprovalInstance');
    }

    public function delete(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Delete:DocumentTemplateApprovalInstance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentTemplateApprovalInstance');
    }

    public function restore(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Restore:DocumentTemplateApprovalInstance');
    }

    public function forceDelete(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('ForceDelete:DocumentTemplateApprovalInstance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentTemplateApprovalInstance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentTemplateApprovalInstance');
    }

    public function replicate(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Replicate:DocumentTemplateApprovalInstance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentTemplateApprovalInstance');
    }

    public function approve(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Approve:DocumentTemplateApprovalInstance');
    }

    public function submit(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Submit:DocumentTemplateApprovalInstance');
    }

    public function review(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Review:DocumentTemplateApprovalInstance');
    }

    public function publish(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Publish:DocumentTemplateApprovalInstance');
    }

    public function unpublish(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Unpublish:DocumentTemplateApprovalInstance');
    }

    public function revise(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Revise:DocumentTemplateApprovalInstance');
    }

    public function archive(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Archive:DocumentTemplateApprovalInstance');
    }

    public function unarchive(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Unarchive:DocumentTemplateApprovalInstance');
    }

    public function markObsolete(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('MarkObsolete:DocumentTemplateApprovalInstance');
    }

    public function completeRetention(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('CompleteRetention:DocumentTemplateApprovalInstance');
    }

    public function destroy(AuthUser $authUser, DocumentTemplateApprovalInstance $documentTemplateApprovalInstance): bool
    {
        return $authUser->can('Destroy:DocumentTemplateApprovalInstance');
    }

}