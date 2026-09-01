<?php

declare(strict_types=1);

namespace App\Domain\TMS\Policies;

use App\Domain\TMS\Models\TrainingProgram;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TrainingProgramPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TrainingProgram');
    }

    public function view(AuthUser $authUser, TrainingProgram $trainingProgram): bool
    {
        return $authUser->can('View:TrainingProgram');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TrainingProgram');
    }

    public function update(AuthUser $authUser, TrainingProgram $trainingProgram): bool
    {
        return $authUser->can('Update:TrainingProgram');
    }

    public function assign(AuthUser $authUser, TrainingProgram $trainingProgram): bool
    {
        return $authUser->can('Assign:TrainingProgram');
    }

    public function delete(AuthUser $authUser, TrainingProgram $trainingProgram): bool
    {
        return $authUser->can('Manage:TrainingProgram');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('Manage:TrainingProgram');
    }
}
