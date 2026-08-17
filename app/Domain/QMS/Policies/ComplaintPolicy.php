<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\Complaint;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ComplaintPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Complaint');
    }

    public function view(AuthUser $authUser, Complaint $complaint): bool
    {
        return $authUser->can('View:Complaint');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Complaint');
    }

    public function update(AuthUser $authUser, Complaint $complaint): bool
    {
        return $authUser->can('Update:Complaint');
    }

    public function delete(AuthUser $authUser, Complaint $complaint): bool
    {
        return $authUser->can('Delete:Complaint');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Complaint');
    }

    public function restore(AuthUser $authUser, Complaint $complaint): bool
    {
        return $authUser->can('Restore:Complaint');
    }

    public function forceDelete(AuthUser $authUser, Complaint $complaint): bool
    {
        return $authUser->can('ForceDelete:Complaint');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Complaint');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Complaint');
    }

    public function replicate(AuthUser $authUser, Complaint $complaint): bool
    {
        return $authUser->can('Replicate:Complaint');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Complaint');
    }
}
