<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ScheduleMGapItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ScheduleMGapItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ScheduleMGapAssessment');
    }

    public function view(AuthUser $authUser, ScheduleMGapItem $scheduleMGapItem): bool
    {
        return $authUser->can('View:ScheduleMGapAssessment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Update:ScheduleMGapAssessment');
    }

    public function update(AuthUser $authUser, ScheduleMGapItem $scheduleMGapItem): bool
    {
        return $authUser->can('Conduct:ScheduleMGapAssessment')
            || $authUser->can('Update:ScheduleMGapAssessment');
    }

    public function delete(AuthUser $authUser, ScheduleMGapItem $scheduleMGapItem): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }
}
