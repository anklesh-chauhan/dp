<?php

declare(strict_types=1);

namespace App\Domain\QMS\Policies;

use App\Domain\QMS\Models\AuditFinding;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AuditFindingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AuditFinding');
    }

    public function view(AuthUser $authUser, AuditFinding $auditFinding): bool
    {
        return $authUser->can('View:AuditFinding');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AuditFinding');
    }

    public function update(AuthUser $authUser, AuditFinding $auditFinding): bool
    {
        return $authUser->can('Update:AuditFinding');
    }

    public function delete(AuthUser $authUser, AuditFinding $auditFinding): bool
    {
        return $authUser->can('Delete:AuditFinding');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AuditFinding');
    }

    public function restore(AuthUser $authUser, AuditFinding $auditFinding): bool
    {
        return $authUser->can('Restore:AuditFinding');
    }

    public function forceDelete(AuthUser $authUser, AuditFinding $auditFinding): bool
    {
        return $authUser->can('ForceDelete:AuditFinding');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AuditFinding');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AuditFinding');
    }

    public function replicate(AuthUser $authUser, AuditFinding $auditFinding): bool
    {
        return $authUser->can('Replicate:AuditFinding');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AuditFinding');
    }
}
