<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RegulationTag;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RegulationTagPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RegulationTag');
    }

    public function view(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('View:RegulationTag');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RegulationTag');
    }

    public function update(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Update:RegulationTag');
    }

    public function delete(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Delete:RegulationTag');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RegulationTag');
    }

    public function restore(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Restore:RegulationTag');
    }

    public function forceDelete(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('ForceDelete:RegulationTag');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RegulationTag');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RegulationTag');
    }

    public function replicate(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Replicate:RegulationTag');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RegulationTag');
    }

    public function approve(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Approve:RegulationTag');
    }

    public function submit(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Submit:RegulationTag');
    }

    public function review(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Review:RegulationTag');
    }

    public function publish(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Publish:RegulationTag');
    }

    public function unpublish(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Unpublish:RegulationTag');
    }

    public function revise(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Revise:RegulationTag');
    }

    public function archive(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Archive:RegulationTag');
    }

    public function unarchive(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Unarchive:RegulationTag');
    }

    public function markObsolete(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('MarkObsolete:RegulationTag');
    }

    public function completeRetention(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('CompleteRetention:RegulationTag');
    }

    public function destroy(AuthUser $authUser, RegulationTag $regulationTag): bool
    {
        return $authUser->can('Destroy:RegulationTag');
    }
}
