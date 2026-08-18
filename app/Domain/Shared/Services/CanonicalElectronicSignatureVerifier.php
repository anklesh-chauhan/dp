<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Contracts\HasSignatureContentDigest;

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

        $contentDigest = $signature instanceof HasSignatureContentDigest
            ? $signature->signatureContentDigest()
            : null;

        $expectedHash = $this->electronicSignatureHasher->hashFor(
            recordKey: $signature->signatureRecordKey(),
            meaning: $meaning,
            signerId: $signerId,
            signedAt: $signedAt,
            reason: $signature->signatureReason(),
            ipAddress: $signature->signatureIpAddress(),
            userAgent: $signature->signatureUserAgent(),
            contentDigest: $contentDigest,
        );

        if (hash_equals($expectedHash, $storedHash)) {
            return true;
        }

        if ($contentDigest === null) {
            return false;
        }

        $legacyHash = $this->electronicSignatureHasher->hashFor(
            recordKey: $signature->signatureRecordKey(),
            meaning: $meaning,
            signerId: $signerId,
            signedAt: $signedAt,
            reason: $signature->signatureReason(),
            ipAddress: $signature->signatureIpAddress(),
            userAgent: $signature->signatureUserAgent(),
        );

        return hash_equals($legacyHash, $storedHash);
    }
}
