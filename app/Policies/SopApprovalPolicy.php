<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SopApproval;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SopApprovalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SopApproval');
    }

    public function view(User $user, SopApproval $sopApproval): bool
    {
        return $user->can('View:SopApproval');
    }

    public function approve(User $user, SopApproval $sopApproval): bool
    {
        return $sopApproval->canBeApprovedBy($user);
    }
}
