<?php

declare(strict_types=1);

namespace App\Domain\TMS\Policies;

use App\Domain\TMS\Models\TrainingAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TrainingAssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TrainingAssignment');
    }

    public function view(AuthUser $authUser, TrainingAssignment $trainingAssignment): bool
    {
        return $authUser->can('View:TrainingAssignment');
    }

    public function complete(AuthUser $authUser, TrainingAssignment $trainingAssignment): bool
    {
        return $authUser->can('Complete:TrainingAssignment')
            && (int) $trainingAssignment->user_id === (int) $authUser->getAuthIdentifier();
    }
}
