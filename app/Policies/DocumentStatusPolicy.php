<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentStatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentStatus');
    }

    public function view(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('View:DocumentStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentStatus');
    }

    public function update(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Update:DocumentStatus');
    }

    public function delete(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Delete:DocumentStatus');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentStatus');
    }

    public function restore(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Restore:DocumentStatus');
    }

    public function forceDelete(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('ForceDelete:DocumentStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentStatus');
    }

    public function replicate(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Replicate:DocumentStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentStatus');
    }

    public function approve(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Approve:DocumentStatus');
    }

    public function submit(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Submit:DocumentStatus');
    }

    public function review(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Review:DocumentStatus');
    }

    public function publish(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Publish:DocumentStatus');
    }

    public function unpublish(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Unpublish:DocumentStatus');
    }

    public function archive(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Archive:DocumentStatus');
    }

    public function unarchive(AuthUser $authUser, DocumentStatus $documentStatus): bool
    {
        return $authUser->can('Unarchive:DocumentStatus');
    }
}
