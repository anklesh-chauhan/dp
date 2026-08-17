<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\LaboratoryOosEvent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LaboratoryOosEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LaboratoryOosEvent');
    }

    public function view(AuthUser $authUser, LaboratoryOosEvent $laboratoryOosEvent): bool
    {
        return $authUser->can('View:LaboratoryOosEvent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LaboratoryOosEvent');
    }

    public function update(AuthUser $authUser, LaboratoryOosEvent $laboratoryOosEvent): bool
    {
        return $authUser->can('Update:LaboratoryOosEvent');
    }

    public function delete(AuthUser $authUser, LaboratoryOosEvent $laboratoryOosEvent): bool
    {
        return $authUser->can('Delete:LaboratoryOosEvent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LaboratoryOosEvent');
    }

    public function restore(AuthUser $authUser, LaboratoryOosEvent $laboratoryOosEvent): bool
    {
        return $authUser->can('Restore:LaboratoryOosEvent');
    }

    public function forceDelete(AuthUser $authUser, LaboratoryOosEvent $laboratoryOosEvent): bool
    {
        return $authUser->can('ForceDelete:LaboratoryOosEvent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LaboratoryOosEvent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LaboratoryOosEvent');
    }

    public function replicate(AuthUser $authUser, LaboratoryOosEvent $laboratoryOosEvent): bool
    {
        return $authUser->can('Replicate:LaboratoryOosEvent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LaboratoryOosEvent');
    }
}
