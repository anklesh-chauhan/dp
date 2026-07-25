<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface ApprovalWorkflowDefinitionSelector
{
    public function selectFor(ApprovableSubject $subject): ?ApprovalWorkflowDefinition;
}
