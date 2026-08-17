<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ProductRecall;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductRecallPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductRecall');
    }

    public function view(AuthUser $authUser, ProductRecall $productRecall): bool
    {
        return $authUser->can('View:ProductRecall');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductRecall');
    }

    public function update(AuthUser $authUser, ProductRecall $productRecall): bool
    {
        return $authUser->can('Update:ProductRecall');
    }

    public function delete(AuthUser $authUser, ProductRecall $productRecall): bool
    {
        return $authUser->can('Delete:ProductRecall');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductRecall');
    }

    public function restore(AuthUser $authUser, ProductRecall $productRecall): bool
    {
        return $authUser->can('Restore:ProductRecall');
    }

    public function forceDelete(AuthUser $authUser, ProductRecall $productRecall): bool
    {
        return $authUser->can('ForceDelete:ProductRecall');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductRecall');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductRecall');
    }

    public function replicate(AuthUser $authUser, ProductRecall $productRecall): bool
    {
        return $authUser->can('Replicate:ProductRecall');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductRecall');
    }
}
