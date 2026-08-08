<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentExecution;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentExecutionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentExecution');
    }

    public function view(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('View:DocumentExecution');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentExecution');
    }

    public function update(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Update:DocumentExecution');
    }

    public function delete(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Delete:DocumentExecution');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DocumentExecution');
    }

    public function restore(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Restore:DocumentExecution');
    }

    public function forceDelete(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('ForceDelete:DocumentExecution');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentExecution');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentExecution');
    }

    public function replicate(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Replicate:DocumentExecution');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentExecution');
    }

    public function approve(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Approve:DocumentExecution');
    }

    public function submit(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Submit:DocumentExecution');
    }

    public function review(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Review:DocumentExecution');
    }

    public function publish(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Publish:DocumentExecution');
    }

    public function unpublish(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Unpublish:DocumentExecution');
    }

    public function revise(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Revise:DocumentExecution');
    }

    public function archive(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Archive:DocumentExecution');
    }

    public function unarchive(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Unarchive:DocumentExecution');
    }

    public function markObsolete(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('MarkObsolete:DocumentExecution');
    }

    public function completeRetention(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('CompleteRetention:DocumentExecution');
    }

    public function destroy(AuthUser $authUser, DocumentExecution $documentExecution): bool
    {
        return $authUser->can('Destroy:DocumentExecution');
    }
}
