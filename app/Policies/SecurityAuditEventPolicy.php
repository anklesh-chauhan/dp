<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SecurityAuditEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SecurityAuditEvent');
    }

    public function view(User $user, SecurityAuditEvent $securityAuditEvent): bool
    {
        return $user->can('View:SecurityAuditEvent');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SecurityAuditEvent $securityAuditEvent): bool
    {
        return false;
    }

    public function delete(User $user, SecurityAuditEvent $securityAuditEvent): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, SecurityAuditEvent $securityAuditEvent): bool
    {
        return false;
    }

    public function forceDelete(User $user, SecurityAuditEvent $securityAuditEvent): bool
    {
        return false;
    }
}
