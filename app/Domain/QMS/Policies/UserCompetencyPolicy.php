<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\UserCompetency;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserCompetencyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UserCompetency');
    }

    public function view(AuthUser $authUser, UserCompetency $userCompetency): bool
    {
        return $authUser->can('View:UserCompetency');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Assign:UserCompetency')
            || $authUser->can('Create:UserCompetency');
    }

    public function update(AuthUser $authUser, UserCompetency $userCompetency): bool
    {
        return $authUser->can('Update:UserCompetency');
    }

    public function assign(AuthUser $authUser): bool
    {
        return $authUser->can('Assign:UserCompetency');
    }

    public function verify(AuthUser $authUser, UserCompetency $userCompetency): bool
    {
        return $authUser->can('Verify:UserCompetency');
    }

    public function manage(AuthUser $authUser, UserCompetency $userCompetency): bool
    {
        return $authUser->can('Manage:UserCompetency');
    }

    public function delete(AuthUser $authUser, UserCompetency $userCompetency): bool
    {
        return $authUser->can('Manage:UserCompetency');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('Manage:UserCompetency');
    }

    public function restore(AuthUser $authUser, UserCompetency $userCompetency): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, UserCompetency $userCompetency): bool
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

    public function replicate(AuthUser $authUser, UserCompetency $userCompetency): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}
