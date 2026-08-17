<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\ManagementReview;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ManagementReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ManagementReview');
    }

    public function view(AuthUser $authUser, ManagementReview $managementReview): bool
    {
        return $authUser->can('View:ManagementReview');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ManagementReview');
    }

    public function update(AuthUser $authUser, ManagementReview $managementReview): bool
    {
        return $authUser->can('Update:ManagementReview');
    }

    public function delete(AuthUser $authUser, ManagementReview $managementReview): bool
    {
        return $authUser->can('Delete:ManagementReview');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ManagementReview');
    }

    public function restore(AuthUser $authUser, ManagementReview $managementReview): bool
    {
        return $authUser->can('Restore:ManagementReview');
    }

    public function forceDelete(AuthUser $authUser, ManagementReview $managementReview): bool
    {
        return $authUser->can('ForceDelete:ManagementReview');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ManagementReview');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ManagementReview');
    }

    public function replicate(AuthUser $authUser, ManagementReview $managementReview): bool
    {
        return $authUser->can('Replicate:ManagementReview');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ManagementReview');
    }
}
