<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\SiteMasterFile;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SiteMasterFilePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SiteMasterFile');
    }

    public function view(AuthUser $authUser, SiteMasterFile $siteMasterFile): bool
    {
        return $authUser->can('View:SiteMasterFile');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SiteMasterFile');
    }

    public function update(AuthUser $authUser, SiteMasterFile $siteMasterFile): bool
    {
        return $authUser->can('Update:SiteMasterFile');
    }

    public function delete(AuthUser $authUser, SiteMasterFile $siteMasterFile): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, SiteMasterFile $siteMasterFile): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, SiteMasterFile $siteMasterFile): bool
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

    public function replicate(AuthUser $authUser, SiteMasterFile $siteMasterFile): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}
