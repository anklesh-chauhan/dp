<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\CsvValidationProject;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CsvValidationProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CsvValidationProject');
    }

    public function view(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('View:CsvValidationProject');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CsvValidationProject');
    }

    public function update(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Update:CsvValidationProject');
    }

    public function delete(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Delete:CsvValidationProject');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CsvValidationProject');
    }

    public function restore(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Restore:CsvValidationProject');
    }

    public function forceDelete(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('ForceDelete:CsvValidationProject');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CsvValidationProject');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CsvValidationProject');
    }

    public function replicate(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Replicate:CsvValidationProject');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CsvValidationProject');
    }

    public function approve(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Approve:CsvValidationProject');
    }

    public function submit(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Submit:CsvValidationProject');
    }

    public function review(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Review:CsvValidationProject');
    }

    public function publish(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Publish:CsvValidationProject');
    }

    public function unpublish(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Unpublish:CsvValidationProject');
    }

    public function revise(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Revise:CsvValidationProject');
    }

    public function archive(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Archive:CsvValidationProject');
    }

    public function unarchive(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Unarchive:CsvValidationProject');
    }

    public function markObsolete(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('MarkObsolete:CsvValidationProject');
    }

    public function completeRetention(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('CompleteRetention:CsvValidationProject');
    }

    public function destroy(AuthUser $authUser, CsvValidationProject $csvValidationProject): bool
    {
        return $authUser->can('Destroy:CsvValidationProject');
    }
}
