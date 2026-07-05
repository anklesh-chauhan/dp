<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DocumentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentType');
    }

    public function view(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('View:DocumentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentType');
    }

    public function update(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Update:DocumentType');
    }

    public function delete(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Delete:DocumentType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentType');
    }

    public function restore(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Restore:DocumentType');
    }

    public function forceDelete(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('ForceDelete:DocumentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentType');
    }

    public function replicate(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Replicate:DocumentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentType');
    }

    public function approve(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Approve:DocumentType');
    }

    public function submit(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Submit:DocumentType');
    }

    public function review(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Review:DocumentType');
    }

    public function publish(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Publish:DocumentType');
    }

    public function unpublish(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Unpublish:DocumentType');
    }

    public function archive(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Archive:DocumentType');
    }

    public function unarchive(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Unarchive:DocumentType');
    }

    public function markObsolete(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('MarkObsolete:DocumentType');
    }

    public function completeRetention(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('CompleteRetention:DocumentType');
    }

    public function destroy(AuthUser $authUser, DocumentType $documentType): bool
    {
        return $authUser->can('Destroy:DocumentType');
    }

}