<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Organization');
    }

    public function view(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('View:Organization');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Organization');
    }

    public function update(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Update:Organization');
    }

    public function delete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Delete:Organization');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Organization');
    }

    public function restore(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Restore:Organization');
    }

    public function forceDelete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('ForceDelete:Organization');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Organization');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Organization');
    }

    public function replicate(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Replicate:Organization');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Organization');
    }

    public function approve(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Approve:Organization');
    }

    public function submit(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Submit:Organization');
    }

    public function review(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Review:Organization');
    }

    public function publish(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Publish:Organization');
    }

    public function unpublish(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Unpublish:Organization');
    }

    public function revise(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Revise:Organization');
    }

    public function archive(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Archive:Organization');
    }

    public function unarchive(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Unarchive:Organization');
    }

    public function markObsolete(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('MarkObsolete:Organization');
    }

    public function completeRetention(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('CompleteRetention:Organization');
    }

    public function destroy(AuthUser $authUser, Organization $organization): bool
    {
        return $authUser->can('Destroy:Organization');
    }
}
