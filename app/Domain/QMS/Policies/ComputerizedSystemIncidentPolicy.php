<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ComputerizedSystemIncident;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ComputerizedSystemIncidentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ComputerizedSystemIncident');
    }

    public function view(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('View:ComputerizedSystemIncident');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ComputerizedSystemIncident');
    }

    public function update(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Update:ComputerizedSystemIncident');
    }

    public function delete(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Restore:ComputerizedSystemIncident');
    }

    public function forceDelete(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('ForceDelete:ComputerizedSystemIncident');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ComputerizedSystemIncident');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ComputerizedSystemIncident');
    }

    public function replicate(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Replicate:ComputerizedSystemIncident');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ComputerizedSystemIncident');
    }

    public function approve(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Approve:ComputerizedSystemIncident');
    }

    public function submit(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Submit:ComputerizedSystemIncident');
    }

    public function review(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Review:ComputerizedSystemIncident');
    }

    public function publish(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Publish:ComputerizedSystemIncident');
    }

    public function unpublish(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Unpublish:ComputerizedSystemIncident');
    }

    public function revise(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Revise:ComputerizedSystemIncident');
    }

    public function archive(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Archive:ComputerizedSystemIncident');
    }

    public function unarchive(AuthUser $authUser, ComputerizedSystemIncident $computerizedSystemIncident): bool
    {
        return $authUser->can('Unarchive:ComputerizedSystemIncident');
    }
}
