<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface ApprovalWorkflowStepDefinition
{
    public function approvalWorkflowStepDefinitionKey(): int|string|null;
}
