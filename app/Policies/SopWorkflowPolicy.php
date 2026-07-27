<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SopWorkflow;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SopWorkflowPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SopWorkflow');
    }

    public function view(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('View:SopWorkflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SopWorkflow');
    }

    public function update(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Update:SopWorkflow');
    }

    public function delete(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Delete:SopWorkflow');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SopWorkflow');
    }

    public function restore(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Restore:SopWorkflow');
    }

    public function forceDelete(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('ForceDelete:SopWorkflow');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SopWorkflow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SopWorkflow');
    }

    public function replicate(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Replicate:SopWorkflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SopWorkflow');
    }

    public function approve(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Approve:SopWorkflow');
    }

    public function submit(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Submit:SopWorkflow');
    }

    public function review(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Review:SopWorkflow');
    }

    public function publish(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Publish:SopWorkflow');
    }

    public function unpublish(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Unpublish:SopWorkflow');
    }

    public function revise(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Revise:SopWorkflow');
    }

    public function archive(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Archive:SopWorkflow');
    }

    public function unarchive(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Unarchive:SopWorkflow');
    }

    public function markObsolete(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('MarkObsolete:SopWorkflow');
    }

    public function completeRetention(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('CompleteRetention:SopWorkflow');
    }

    public function destroy(AuthUser $authUser, SopWorkflow $sopWorkflow): bool
    {
        return $authUser->can('Destroy:SopWorkflow');
    }
}
