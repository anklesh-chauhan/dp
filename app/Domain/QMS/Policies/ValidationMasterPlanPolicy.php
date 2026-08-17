<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ValidationMasterPlan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ValidationMasterPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ValidationMasterPlan');
    }

    public function view(AuthUser $authUser, ValidationMasterPlan $validationMasterPlan): bool
    {
        return $authUser->can('View:ValidationMasterPlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ValidationMasterPlan');
    }

    public function update(AuthUser $authUser, ValidationMasterPlan $validationMasterPlan): bool
    {
        return $authUser->can('Update:ValidationMasterPlan');
    }

    public function delete(AuthUser $authUser, ValidationMasterPlan $validationMasterPlan): bool
    {
        return $authUser->can('Delete:ValidationMasterPlan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ValidationMasterPlan');
    }

    public function restore(AuthUser $authUser, ValidationMasterPlan $validationMasterPlan): bool
    {
        return $authUser->can('Restore:ValidationMasterPlan');
    }

    public function forceDelete(AuthUser $authUser, ValidationMasterPlan $validationMasterPlan): bool
    {
        return $authUser->can('ForceDelete:ValidationMasterPlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ValidationMasterPlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ValidationMasterPlan');
    }

    public function replicate(AuthUser $authUser, ValidationMasterPlan $validationMasterPlan): bool
    {
        return $authUser->can('Replicate:ValidationMasterPlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ValidationMasterPlan');
    }
}
