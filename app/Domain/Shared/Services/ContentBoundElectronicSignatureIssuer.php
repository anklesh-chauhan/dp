<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ProvidesElectronicSignatureContent;
use App\Models\User;
use DateTimeInterface;

final class ContentBoundElectronicSignatureIssuer
{
    public function __construct(
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
        private readonly ElectronicSignatureContentDigester $contentDigester,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{0: string, 1: array<string, mixed>}
     */
    public function issue(
        User $signer,
        ProvidesElectronicSignatureContent $subject,
        int|string|null $recordKey,
        string $meaning,
        DateTimeInterface $signedAt,
        ?string $reason,
        ?string $ipAddress,
        ?string $userAgent,
        array $context = [],
    ): array {
        $digest = $this->contentDigester->digest($subject->electronicSignatureContentPayload());

        $hash = $this->electronicSignatureHasher->issueFor(
            signer: $signer,
            recordKey: $recordKey,
            meaning: $meaning,
            signedAt: $signedAt,
            reason: $reason,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            contentDigest: $digest,
        );

        $context['content_digest'] = $digest;

        return [$hash, $context];
    }
}
