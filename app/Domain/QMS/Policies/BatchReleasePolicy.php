<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\BatchRelease;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BatchReleasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BatchRelease');
    }

    public function view(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('View:BatchRelease');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BatchRelease');
    }

    public function update(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Update:BatchRelease');
    }

    public function delete(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Restore:BatchRelease');
    }

    public function forceDelete(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('ForceDelete:BatchRelease');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BatchRelease');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BatchRelease');
    }

    public function replicate(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Replicate:BatchRelease');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BatchRelease');
    }

    public function approve(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Approve:BatchRelease');
    }

    public function submit(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Submit:BatchRelease');
    }

    public function review(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Review:BatchRelease');
    }

    public function publish(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Publish:BatchRelease');
    }

    public function unpublish(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Unpublish:BatchRelease');
    }

    public function revise(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Revise:BatchRelease');
    }

    public function archive(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Archive:BatchRelease');
    }

    public function unarchive(AuthUser $authUser, BatchRelease $batchRelease): bool
    {
        return $authUser->can('Unarchive:BatchRelease');
    }
}
