<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface ApprovalWorkflowDefinition
{
    public function approvalWorkflowDefinitionKey(): int|string|null;

    /**
     * @return iterable<ApprovalWorkflowStepDefinition>
     */
    public function approvalWorkflowStepDefinitions(): iterable;
}
