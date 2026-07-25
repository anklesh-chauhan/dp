<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use DateTimeInterface;

interface ApprovalDecisionPersistence
{
    public function recordDecision(
        ApprovalInstance $approval,
        string $decisionCode,
        int $decidedById,
        ?string $comments,
        DateTimeInterface $decidedAt,
        ?string $signatureHash = null,
        ?string $signatureIpAddress = null,
        ?string $signatureUserAgent = null,
    ): ApprovalInstance;
}
