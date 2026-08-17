<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ProductReturn;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductReturn');
    }

    public function view(AuthUser $authUser, ProductReturn $productReturn): bool
    {
        return $authUser->can('View:ProductReturn');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductReturn');
    }

    public function update(AuthUser $authUser, ProductReturn $productReturn): bool
    {
        return $authUser->can('Update:ProductReturn');
    }

    public function delete(AuthUser $authUser, ProductReturn $productReturn): bool
    {
        return $authUser->can('Delete:ProductReturn');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductReturn');
    }

    public function restore(AuthUser $authUser, ProductReturn $productReturn): bool
    {
        return $authUser->can('Restore:ProductReturn');
    }

    public function forceDelete(AuthUser $authUser, ProductReturn $productReturn): bool
    {
        return $authUser->can('ForceDelete:ProductReturn');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductReturn');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductReturn');
    }

    public function replicate(AuthUser $authUser, ProductReturn $productReturn): bool
    {
        return $authUser->can('Replicate:ProductReturn');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductReturn');
    }
}
