<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SopDocument;
use Illuminate\Auth\Access\HandlesAuthorization;

class SopDocumentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SopDocument');
    }

    public function view(AuthUser $authUser, SopDocument $sopDocument): bool
    {
        return $authUser->can('View:SopDocument');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SopDocument');
    }

    public function update(AuthUser $authUser, SopDocument $sopDocument): bool
    {
        return $authUser->can('Update:SopDocument');
    }

    public function delete(AuthUser $authUser, SopDocument $sopDocument): bool
    {
        return $authUser->can('Delete:SopDocument');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SopDocument');
    }

    public function restore(AuthUser $authUser, SopDocument $sopDocument): bool
    {
        return $authUser->can('Restore:SopDocument');
    }

    public function forceDelete(AuthUser $authUser, SopDocument $sopDocument): bool
    {
        return $authUser->can('ForceDelete:SopDocument');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SopDocument');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SopDocument');
    }

    public function replicate(AuthUser $authUser, SopDocument $sopDocument): bool
    {
        return $authUser->can('Replicate:SopDocument');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SopDocument');
    }

    public function approve(AuthUser $authUser, SopDocument $sopDocument): bool
    {
        return $authUser->can('Approve:SopDocument');
    }

}