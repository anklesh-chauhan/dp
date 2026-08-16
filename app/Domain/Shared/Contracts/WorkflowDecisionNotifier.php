<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Models\User;

interface WorkflowDecisionNotifier
{
    public function notifyDecision(
        ApprovalInstance $approval,
        User $actor,
        ApprovalDecisionCode $decision,
    ): void;
}
