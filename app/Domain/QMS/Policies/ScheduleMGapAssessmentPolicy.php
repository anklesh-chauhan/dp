<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ScheduleMGapAssessment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ScheduleMGapAssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ScheduleMGapAssessment');
    }

    public function view(AuthUser $authUser, ScheduleMGapAssessment $scheduleMGapAssessment): bool
    {
        return $authUser->can('View:ScheduleMGapAssessment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ScheduleMGapAssessment');
    }

    public function update(AuthUser $authUser, ScheduleMGapAssessment $scheduleMGapAssessment): bool
    {
        return $authUser->can('Update:ScheduleMGapAssessment');
    }

    public function delete(AuthUser $authUser, ScheduleMGapAssessment $scheduleMGapAssessment): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, ScheduleMGapAssessment $scheduleMGapAssessment): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, ScheduleMGapAssessment $scheduleMGapAssessment): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, ScheduleMGapAssessment $scheduleMGapAssessment): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}
