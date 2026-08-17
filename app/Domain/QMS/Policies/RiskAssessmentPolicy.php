<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\RiskAssessment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RiskAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RiskAssessment');
    }

    public function view(AuthUser $authUser, RiskAssessment $riskAssessment): bool
    {
        return $authUser->can('View:RiskAssessment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RiskAssessment');
    }

    public function update(AuthUser $authUser, RiskAssessment $riskAssessment): bool
    {
        return $authUser->can('Update:RiskAssessment');
    }

    public function delete(AuthUser $authUser, RiskAssessment $riskAssessment): bool
    {
        return $authUser->can('Delete:RiskAssessment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RiskAssessment');
    }

    public function restore(AuthUser $authUser, RiskAssessment $riskAssessment): bool
    {
        return $authUser->can('Restore:RiskAssessment');
    }

    public function forceDelete(AuthUser $authUser, RiskAssessment $riskAssessment): bool
    {
        return $authUser->can('ForceDelete:RiskAssessment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RiskAssessment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RiskAssessment');
    }

    public function replicate(AuthUser $authUser, RiskAssessment $riskAssessment): bool
    {
        return $authUser->can('Replicate:RiskAssessment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RiskAssessment');
    }
}
