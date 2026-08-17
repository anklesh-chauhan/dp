<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\EquipmentMaintenance;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EquipmentMaintenancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EquipmentMaintenance');
    }

    public function view(AuthUser $authUser, EquipmentMaintenance $equipmentMaintenance): bool
    {
        return $authUser->can('View:EquipmentMaintenance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EquipmentMaintenance');
    }

    public function update(AuthUser $authUser, EquipmentMaintenance $equipmentMaintenance): bool
    {
        return $authUser->can('Update:EquipmentMaintenance');
    }

    public function delete(AuthUser $authUser, EquipmentMaintenance $equipmentMaintenance): bool
    {
        return $authUser->can('Delete:EquipmentMaintenance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EquipmentMaintenance');
    }

    public function restore(AuthUser $authUser, EquipmentMaintenance $equipmentMaintenance): bool
    {
        return $authUser->can('Restore:EquipmentMaintenance');
    }

    public function forceDelete(AuthUser $authUser, EquipmentMaintenance $equipmentMaintenance): bool
    {
        return $authUser->can('ForceDelete:EquipmentMaintenance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EquipmentMaintenance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EquipmentMaintenance');
    }

    public function replicate(AuthUser $authUser, EquipmentMaintenance $equipmentMaintenance): bool
    {
        return $authUser->can('Replicate:EquipmentMaintenance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EquipmentMaintenance');
    }
}
