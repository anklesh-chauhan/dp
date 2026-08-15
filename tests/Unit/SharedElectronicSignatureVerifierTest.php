<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Services\CanonicalElectronicSignatureVerifier;
use App\Domain\Shared\Services\Sha256ElectronicSignatureHasher;

it('verifies canonical electronic signature metadata', function (): void {
    $hasher = new Sha256ElectronicSignatureHasher;
    $signedAt = new DateTimeImmutable('2026-07-25T05:00:00Z');
    $storedHash = $hasher->hashFor(
        recordKey: 42,
        meaning: 'approved',
        signerId: 21,
        signedAt: $signedAt,
        reason: 'Reviewed and approved.',
        ipAddress: '203.0.113.42',
        userAgent: 'QualiGxP Signature Test',
    );
    $signature = electronicSignatureRecord(
        signedAt: $signedAt,
        storedHash: $storedHash,
    );

    expect((new CanonicalElectronicSignatureVerifier($hasher))->isValid($signature))->toBeTrue();
});

it('rejects tampered electronic signature metadata', function (): void {
    $hasher = new Sha256ElectronicSignatureHasher;
    $signedAt = new DateTimeImmutable('2026-07-25T05:00:00Z');
    $storedHash = $hasher->hashFor(
        recordKey: 42,
        meaning: 'approved',
        signerId: 21,
        signedAt: $signedAt,
        reason: 'Reviewed and approved.',
        ipAddress: '203.0.113.42',
        userAgent: 'QualiGxP Signature Test',
    );
    $signature = electronicSignatureRecord(
        signedAt: $signedAt,
        storedHash: $storedHash,
        reason: 'Tampered reason.',
    );

    expect((new CanonicalElectronicSignatureVerifier($hasher))->isValid($signature))->toBeFalse();
});

it('rejects incomplete electronic signature metadata', function (): void {
    $hasher = new Sha256ElectronicSignatureHasher;
    $signature = electronicSignatureRecord(
        signedAt: null,
        storedHash: null,
    );

    expect((new CanonicalElectronicSignatureVerifier($hasher))->isValid($signature))->toBeFalse();
});

arch('Shared electronic signature verification is module neutral')
    ->expect([
        ElectronicSignatureRecord::class,
        ElectronicSignatureVerifier::class,
        CanonicalElectronicSignatureVerifier::class,
    ])
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);

function electronicSignatureRecord(
    ?DateTimeInterface $signedAt,
    ?string $storedHash,
    ?string $reason = 'Reviewed and approved.',
): ElectronicSignatureRecord {
    return new class($signedAt, $storedHash, $reason) implements ElectronicSignatureRecord
    {
        public function __construct(
            private readonly ?DateTimeInterface $signedAt,
            private readonly ?string $storedHash,
            private readonly ?string $reason,
        ) {}

        public function signatureRecordKey(): int
        {
            return 42;
        }

        public function signatureMeaning(): string
        {
            return 'approved';
        }

        public function signatureSignerId(): int
        {
            return 21;
        }

        public function signatureTimestamp(): ?DateTimeInterface
        {
            return $this->signedAt;
        }

        public function signatureHash(): ?string
        {
            return $this->storedHash;
        }

        public function signatureReason(): ?string
        {
            return $this->reason;
        }

        public function signatureIpAddress(): string
        {
            return '203.0.113.42';
        }

        public function signatureUserAgent(): string
        {
            return 'QualiGxP Signature Test';
        }
    };
}
