<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DocumentImportBatch;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentImportBatchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentImportBatch');
    }

    public function view(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('View:DocumentImportBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentImportBatch');
    }

    public function update(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Update:DocumentImportBatch');
    }

    public function delete(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Delete:DocumentImportBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentImportBatch');
    }

    public function restore(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Restore:DocumentImportBatch');
    }

    public function forceDelete(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('ForceDelete:DocumentImportBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentImportBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentImportBatch');
    }

    public function replicate(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Replicate:DocumentImportBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentImportBatch');
    }

    public function approve(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Approve:DocumentImportBatch');
    }

    public function submit(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Submit:DocumentImportBatch');
    }

    public function review(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Review:DocumentImportBatch');
    }

    public function publish(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Publish:DocumentImportBatch');
    }

    public function unpublish(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Unpublish:DocumentImportBatch');
    }

    public function revise(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Revise:DocumentImportBatch');
    }

    public function archive(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Archive:DocumentImportBatch');
    }

    public function unarchive(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Unarchive:DocumentImportBatch');
    }

    public function markObsolete(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('MarkObsolete:DocumentImportBatch');
    }

    public function completeRetention(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('CompleteRetention:DocumentImportBatch');
    }

    public function destroy(AuthUser $authUser, DocumentImportBatch $documentImportBatch): bool
    {
        return $authUser->can('Destroy:DocumentImportBatch');
    }

}