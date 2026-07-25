<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use DateTimeInterface;

interface ApprovalInstance extends ElectronicSignatureRecord
{
    public function approvalInstanceKey(): int|string|null;

    public function approvalInstanceSubject(): ApprovableSubject;

    public function approvalInstanceWorkflowStepDefinition(): ApprovalWorkflowStepDefinition;

    public function approvalInstanceDecisionCode(): ?string;

    public function approvalInstanceApproverId(): ?int;

    public function approvalInstanceComments(): ?string;

    public function approvalInstanceDecidedAt(): ?DateTimeInterface;

    public function approvalInstanceSignatureHash(): ?string;
}
