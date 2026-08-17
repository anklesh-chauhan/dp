<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ProductQualityReview;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductQualityReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductQualityReview');
    }

    public function view(AuthUser $authUser, ProductQualityReview $productQualityReview): bool
    {
        return $authUser->can('View:ProductQualityReview');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductQualityReview');
    }

    public function update(AuthUser $authUser, ProductQualityReview $productQualityReview): bool
    {
        return $authUser->can('Update:ProductQualityReview');
    }

    public function delete(AuthUser $authUser, ProductQualityReview $productQualityReview): bool
    {
        return $authUser->can('Delete:ProductQualityReview');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductQualityReview');
    }

    public function restore(AuthUser $authUser, ProductQualityReview $productQualityReview): bool
    {
        return $authUser->can('Restore:ProductQualityReview');
    }

    public function forceDelete(AuthUser $authUser, ProductQualityReview $productQualityReview): bool
    {
        return $authUser->can('ForceDelete:ProductQualityReview');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductQualityReview');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductQualityReview');
    }

    public function replicate(AuthUser $authUser, ProductQualityReview $productQualityReview): bool
    {
        return $authUser->can('Replicate:ProductQualityReview');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductQualityReview');
    }
}
