<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\EquipmentQualification;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EquipmentQualificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EquipmentQualification');
    }

    public function view(AuthUser $authUser, EquipmentQualification $equipmentQualification): bool
    {
        return $authUser->can('View:EquipmentQualification');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EquipmentQualification');
    }

    public function update(AuthUser $authUser, EquipmentQualification $equipmentQualification): bool
    {
        return $authUser->can('Update:EquipmentQualification');
    }

    public function delete(AuthUser $authUser, EquipmentQualification $equipmentQualification): bool
    {
        return $authUser->can('Delete:EquipmentQualification');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EquipmentQualification');
    }

    public function restore(AuthUser $authUser, EquipmentQualification $equipmentQualification): bool
    {
        return $authUser->can('Restore:EquipmentQualification');
    }

    public function forceDelete(AuthUser $authUser, EquipmentQualification $equipmentQualification): bool
    {
        return $authUser->can('ForceDelete:EquipmentQualification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EquipmentQualification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EquipmentQualification');
    }

    public function replicate(AuthUser $authUser, EquipmentQualification $equipmentQualification): bool
    {
        return $authUser->can('Replicate:EquipmentQualification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EquipmentQualification');
    }
}
