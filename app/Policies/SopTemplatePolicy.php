<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SopTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SopTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SopTemplate');
    }

    public function view(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('View:SopTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SopTemplate');
    }

    public function update(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('Update:SopTemplate');
    }

    public function delete(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('Delete:SopTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SopTemplate');
    }

    public function restore(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('Restore:SopTemplate');
    }

    public function forceDelete(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('ForceDelete:SopTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SopTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SopTemplate');
    }

    public function replicate(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('Replicate:SopTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SopTemplate');
    }

    public function approve(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('Approve:SopTemplate');
    }

    public function publish(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $this->approve($authUser, $sopTemplate);
    }

    public function archive(AuthUser $authUser, SopTemplate $sopTemplate): bool
    {
        return $authUser->can('Update:SopTemplate');
    }
}
