<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class Sha256ElectronicSignatureHasher implements ElectronicSignatureHasher
{
    public function hashFor(
        int|string|null $recordKey,
        string $meaning,
        int|string $signerId,
        DateTimeInterface $signedAt,
        ?string $reason,
        ?string $ipAddress,
        ?string $userAgent,
    ): string {
        $canonicalMetadata = json_encode([
            'record_key' => $recordKey,
            'meaning' => $meaning,
            'signer_id' => $signerId,
            'signed_at' => DateTimeImmutable::createFromInterface($signedAt)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z'),
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $canonicalMetadata);
    }
}
