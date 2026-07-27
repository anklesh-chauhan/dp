<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProductLicense;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductLicensePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductLicense');
    }

    public function view(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('View:ProductLicense');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductLicense');
    }

    public function update(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Update:ProductLicense');
    }

    public function delete(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Delete:ProductLicense');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductLicense');
    }

    public function restore(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Restore:ProductLicense');
    }

    public function forceDelete(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('ForceDelete:ProductLicense');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductLicense');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductLicense');
    }

    public function replicate(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Replicate:ProductLicense');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductLicense');
    }

    public function approve(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Approve:ProductLicense');
    }

    public function submit(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Submit:ProductLicense');
    }

    public function review(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Review:ProductLicense');
    }

    public function publish(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Publish:ProductLicense');
    }

    public function unpublish(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Unpublish:ProductLicense');
    }

    public function revise(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Revise:ProductLicense');
    }

    public function archive(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Archive:ProductLicense');
    }

    public function unarchive(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Unarchive:ProductLicense');
    }

    public function markObsolete(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('MarkObsolete:ProductLicense');
    }

    public function completeRetention(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('CompleteRetention:ProductLicense');
    }

    public function destroy(AuthUser $authUser, ProductLicense $productLicense): bool
    {
        return $authUser->can('Destroy:ProductLicense');
    }

}