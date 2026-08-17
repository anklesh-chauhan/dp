<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\EquipmentCalibration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EquipmentCalibrationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EquipmentCalibration');
    }

    public function view(AuthUser $authUser, EquipmentCalibration $equipmentCalibration): bool
    {
        return $authUser->can('View:EquipmentCalibration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EquipmentCalibration');
    }

    public function update(AuthUser $authUser, EquipmentCalibration $equipmentCalibration): bool
    {
        return $authUser->can('Update:EquipmentCalibration');
    }

    public function delete(AuthUser $authUser, EquipmentCalibration $equipmentCalibration): bool
    {
        return $authUser->can('Delete:EquipmentCalibration');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EquipmentCalibration');
    }

    public function restore(AuthUser $authUser, EquipmentCalibration $equipmentCalibration): bool
    {
        return $authUser->can('Restore:EquipmentCalibration');
    }

    public function forceDelete(AuthUser $authUser, EquipmentCalibration $equipmentCalibration): bool
    {
        return $authUser->can('ForceDelete:EquipmentCalibration');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EquipmentCalibration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EquipmentCalibration');
    }

    public function replicate(AuthUser $authUser, EquipmentCalibration $equipmentCalibration): bool
    {
        return $authUser->can('Replicate:EquipmentCalibration');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EquipmentCalibration');
    }
}
