<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ApprovalDecision;
use App\Models\SopApproval;
use App\Models\User;

class SopApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sop.approvals.view_any');
    }

    public function view(User $user, SopApproval $sopApproval): bool
    {
        return $user->can('sop.approvals.view');
    }

    public function approve(User $user, SopApproval $sopApproval): bool
    {
        return $user->can('sop.documents.approve') && $sopApproval->decision === ApprovalDecision::Pending;
    }
}
