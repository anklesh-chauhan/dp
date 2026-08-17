<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\EquipmentAsset;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EquipmentAssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EquipmentAsset');
    }

    public function view(AuthUser $authUser, EquipmentAsset $equipmentAsset): bool
    {
        return $authUser->can('View:EquipmentAsset');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EquipmentAsset');
    }

    public function update(AuthUser $authUser, EquipmentAsset $equipmentAsset): bool
    {
        return $authUser->can('Update:EquipmentAsset');
    }

    public function delete(AuthUser $authUser, EquipmentAsset $equipmentAsset): bool
    {
        return $authUser->can('Delete:EquipmentAsset');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EquipmentAsset');
    }

    public function restore(AuthUser $authUser, EquipmentAsset $equipmentAsset): bool
    {
        return $authUser->can('Restore:EquipmentAsset');
    }

    public function forceDelete(AuthUser $authUser, EquipmentAsset $equipmentAsset): bool
    {
        return $authUser->can('ForceDelete:EquipmentAsset');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EquipmentAsset');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EquipmentAsset');
    }

    public function replicate(AuthUser $authUser, EquipmentAsset $equipmentAsset): bool
    {
        return $authUser->can('Replicate:EquipmentAsset');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EquipmentAsset');
    }
}
