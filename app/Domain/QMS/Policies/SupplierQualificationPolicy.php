<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\SupplierQualification;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SupplierQualificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierQualification');
    }

    public function view(AuthUser $authUser, SupplierQualification $supplierQualification): bool
    {
        return $authUser->can('View:SupplierQualification');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierQualification');
    }

    public function update(AuthUser $authUser, SupplierQualification $supplierQualification): bool
    {
        return $authUser->can('Update:SupplierQualification');
    }

    public function delete(AuthUser $authUser, SupplierQualification $supplierQualification): bool
    {
        return $authUser->can('Delete:SupplierQualification');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SupplierQualification');
    }

    public function restore(AuthUser $authUser, SupplierQualification $supplierQualification): bool
    {
        return $authUser->can('Restore:SupplierQualification');
    }

    public function forceDelete(AuthUser $authUser, SupplierQualification $supplierQualification): bool
    {
        return $authUser->can('ForceDelete:SupplierQualification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierQualification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierQualification');
    }

    public function replicate(AuthUser $authUser, SupplierQualification $supplierQualification): bool
    {
        return $authUser->can('Replicate:SupplierQualification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierQualification');
    }
}
