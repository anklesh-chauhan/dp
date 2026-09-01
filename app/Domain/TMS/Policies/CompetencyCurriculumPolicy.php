<?php

declare(strict_types=1);

namespace App\Domain\TMS\Policies;

use App\Domain\TMS\Models\CompetencyCurriculum;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CompetencyCurriculumPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompetencyCurriculum');
    }

    public function view(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
    {
        return $authUser->can('View:CompetencyCurriculum');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompetencyCurriculum');
    }

    public function update(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
    {
        return $authUser->can('Update:CompetencyCurriculum');
    }

    public function assign(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
    {
        return $authUser->can('Assign:CompetencyCurriculum');
    }

    public function manage(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
    {
        return $authUser->can('Manage:CompetencyCurriculum');
    }

    public function delete(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
    {
        return $authUser->can('Manage:CompetencyCurriculum');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('Manage:CompetencyCurriculum');
    }

    public function restore(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
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

    public function replicate(AuthUser $authUser, CompetencyCurriculum $competencyCurriculum): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Update:CompetencyCurriculum');
    }
}
