<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use DateTimeInterface;

interface ElectronicSignatureHasher
{
    public function hashFor(
        int|string|null $recordKey,
        string $meaning,
        int|string $signerId,
        DateTimeInterface $signedAt,
        ?string $reason,
        ?string $ipAddress,
        ?string $userAgent,
    ): string;
}
