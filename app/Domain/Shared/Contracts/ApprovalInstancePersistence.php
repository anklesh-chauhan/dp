<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface ApprovalInstancePersistence
{
    public function initializeFor(
        ApprovableSubject $subject,
        ApprovalWorkflowDefinition $workflow,
    ): void;
}
