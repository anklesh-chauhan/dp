<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Services\Sha256ElectronicSignatureHasher;

it('hashes canonical electronic signature metadata deterministically', function (): void {
    $hasher = new Sha256ElectronicSignatureHasher;
    $signedAt = new DateTimeImmutable('2026-07-25T10:30:00.123456+05:30');

    $hash = $hasher->hashFor(
        recordKey: 42,
        meaning: 'approved',
        signerId: 21,
        signedAt: $signedAt,
        reason: 'Reviewed and approved.',
        ipAddress: '203.0.113.42',
        userAgent: 'QualiGxP Signature Test',
    );

    expect($hash)
        ->toBe('c4d3b787d2e13c5cfdeb4612a0d95a7a03396ee244cb0ae30fc9e801131feea6')
        ->and($hasher->hashFor(
            recordKey: 42,
            meaning: 'approved',
            signerId: 21,
            signedAt: $signedAt->setTimezone(new DateTimeZone('UTC')),
            reason: 'Reviewed and approved.',
            ipAddress: '203.0.113.42',
            userAgent: 'QualiGxP Signature Test',
        ))->toBe($hash);
});

it('changes the signature hash when attributable metadata changes', function (
    string $meaning,
    int $signerId,
    ?string $reason,
    ?string $ipAddress,
    ?string $userAgent,
): void {
    $hasher = new Sha256ElectronicSignatureHasher;
    $signedAt = new DateTimeImmutable('2026-07-25T05:00:00.123456Z');
    $baselineHash = $hasher->hashFor(
        recordKey: 42,
        meaning: 'approved',
        signerId: 21,
        signedAt: $signedAt,
        reason: 'Reviewed and approved.',
        ipAddress: '203.0.113.42',
        userAgent: 'QualiGxP Signature Test',
    );

    expect($hasher->hashFor(
        recordKey: 42,
        meaning: $meaning,
        signerId: $signerId,
        signedAt: $signedAt,
        reason: $reason,
        ipAddress: $ipAddress,
        userAgent: $userAgent,
    ))->not->toBe($baselineHash);
})->with([
    'meaning' => ['rejected', 21, 'Reviewed and approved.', '203.0.113.42', 'QualiGxP Signature Test'],
    'signer' => ['approved', 22, 'Reviewed and approved.', '203.0.113.42', 'QualiGxP Signature Test'],
    'reason' => ['approved', 21, 'Approved with a different reason.', '203.0.113.42', 'QualiGxP Signature Test'],
    'IP address' => ['approved', 21, 'Reviewed and approved.', '203.0.113.43', 'QualiGxP Signature Test'],
    'user agent' => ['approved', 21, 'Reviewed and approved.', '203.0.113.42', 'Another Client'],
]);

arch('Shared electronic signature hashing is module neutral')
    ->expect([
        ElectronicSignatureHasher::class,
        Sha256ElectronicSignatureHasher::class,
    ])
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);
