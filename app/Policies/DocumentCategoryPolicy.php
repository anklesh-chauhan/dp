<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DocumentCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentCategory');
    }

    public function view(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('View:DocumentCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentCategory');
    }

    public function update(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Update:DocumentCategory');
    }

    public function delete(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Delete:DocumentCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentCategory');
    }

    public function restore(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Restore:DocumentCategory');
    }

    public function forceDelete(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('ForceDelete:DocumentCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentCategory');
    }

    public function replicate(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Replicate:DocumentCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentCategory');
    }

    public function approve(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Approve:DocumentCategory');
    }

    public function submit(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Submit:DocumentCategory');
    }

    public function review(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Review:DocumentCategory');
    }

    public function publish(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Publish:DocumentCategory');
    }

    public function unpublish(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Unpublish:DocumentCategory');
    }

    public function archive(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Archive:DocumentCategory');
    }

    public function unarchive(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Unarchive:DocumentCategory');
    }

    public function markObsolete(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('MarkObsolete:DocumentCategory');
    }

    public function completeRetention(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('CompleteRetention:DocumentCategory');
    }

    public function destroy(AuthUser $authUser, DocumentCategory $documentCategory): bool
    {
        return $authUser->can('Destroy:DocumentCategory');
    }

}