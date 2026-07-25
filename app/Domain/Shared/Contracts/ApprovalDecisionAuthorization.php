<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Models\User;

interface ApprovalDecisionAuthorization
{
    public function authorizeDecision(ApprovalInstance $approval, User $user): void;
}
