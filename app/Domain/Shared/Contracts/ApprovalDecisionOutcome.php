<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Models\User;

interface ApprovalDecisionOutcome
{
    public function applyOutcome(
        ApprovalInstance $approval,
        string $decisionCode,
        User $decidedBy,
    ): ApprovalInstance;
}
