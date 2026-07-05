<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DocumentIssuance;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentIssuancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentIssuance');
    }

    public function view(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('View:DocumentIssuance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentIssuance');
    }

    public function update(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Update:DocumentIssuance');
    }

    public function delete(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Delete:DocumentIssuance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentIssuance');
    }

    public function restore(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Restore:DocumentIssuance');
    }

    public function forceDelete(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('ForceDelete:DocumentIssuance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentIssuance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentIssuance');
    }

    public function replicate(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Replicate:DocumentIssuance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentIssuance');
    }

    public function approve(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Approve:DocumentIssuance');
    }

    public function submit(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Submit:DocumentIssuance');
    }

    public function review(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Review:DocumentIssuance');
    }

    public function publish(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Publish:DocumentIssuance');
    }

    public function unpublish(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Unpublish:DocumentIssuance');
    }

    public function archive(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Archive:DocumentIssuance');
    }

    public function unarchive(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Unarchive:DocumentIssuance');
    }

    public function markObsolete(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('MarkObsolete:DocumentIssuance');
    }

    public function completeRetention(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('CompleteRetention:DocumentIssuance');
    }

    public function destroy(AuthUser $authUser, DocumentIssuance $documentIssuance): bool
    {
        return $authUser->can('Destroy:DocumentIssuance');
    }

}