<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\InternalAudit;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InternalAuditPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InternalAudit');
    }

    public function view(AuthUser $authUser, InternalAudit $internalAudit): bool
    {
        return $authUser->can('View:InternalAudit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InternalAudit');
    }

    public function update(AuthUser $authUser, InternalAudit $internalAudit): bool
    {
        return $authUser->can('Update:InternalAudit');
    }

    public function delete(AuthUser $authUser, InternalAudit $internalAudit): bool
    {
        return $authUser->can('Delete:InternalAudit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InternalAudit');
    }

    public function restore(AuthUser $authUser, InternalAudit $internalAudit): bool
    {
        return $authUser->can('Restore:InternalAudit');
    }

    public function forceDelete(AuthUser $authUser, InternalAudit $internalAudit): bool
    {
        return $authUser->can('ForceDelete:InternalAudit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InternalAudit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InternalAudit');
    }

    public function replicate(AuthUser $authUser, InternalAudit $internalAudit): bool
    {
        return $authUser->can('Replicate:InternalAudit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InternalAudit');
    }
}
