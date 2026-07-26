<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Domain\QMS\Models\Deviation;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeviationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Deviation');
    }

    public function view(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('View:Deviation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Deviation');
    }

    public function update(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Update:Deviation');
    }

    public function delete(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Delete:Deviation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Deviation');
    }

    public function restore(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Restore:Deviation');
    }

    public function forceDelete(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('ForceDelete:Deviation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Deviation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Deviation');
    }

    public function replicate(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Replicate:Deviation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Deviation');
    }

    public function approve(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Approve:Deviation');
    }

    public function submit(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Submit:Deviation');
    }

    public function review(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Review:Deviation');
    }

    public function publish(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Publish:Deviation');
    }

    public function unpublish(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Unpublish:Deviation');
    }

    public function archive(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Archive:Deviation');
    }

    public function unarchive(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Unarchive:Deviation');
    }

    public function markObsolete(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('MarkObsolete:Deviation');
    }

    public function completeRetention(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('CompleteRetention:Deviation');
    }

    public function destroy(AuthUser $authUser, Deviation $deviation): bool
    {
        return $authUser->can('Destroy:Deviation');
    }

}