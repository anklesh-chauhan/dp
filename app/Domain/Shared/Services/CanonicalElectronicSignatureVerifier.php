<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;

class CanonicalElectronicSignatureVerifier implements ElectronicSignatureVerifier
{
    public function __construct(private readonly ElectronicSignatureHasher $electronicSignatureHasher) {}

    public function isValid(ElectronicSignatureRecord $signature): bool
    {
        $meaning = $signature->signatureMeaning();
        $signerId = $signature->signatureSignerId();
        $signedAt = $signature->signatureTimestamp();
        $storedHash = $signature->signatureHash();

        if ($meaning === null || $signerId === null || $signedAt === null || $storedHash === null) {
            return false;
        }

        $expectedHash = $this->electronicSignatureHasher->hashFor(
            recordKey: $signature->signatureRecordKey(),
            meaning: $meaning,
            signerId: $signerId,
            signedAt: $signedAt,
            reason: $signature->signatureReason(),
            ipAddress: $signature->signatureIpAddress(),
            userAgent: $signature->signatureUserAgent(),
        );

        return hash_equals($expectedHash, $storedHash);
    }
}
