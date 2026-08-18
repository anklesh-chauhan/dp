<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Models\User;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class Sha256ElectronicSignatureHasher implements ElectronicSignatureHasher
{
    public function __construct(private readonly ?ElectronicSignatureAuthenticator $authenticator = null) {}

    public function hashFor(
        int|string|null $recordKey,
        string $meaning,
        int|string $signerId,
        DateTimeInterface $signedAt,
        ?string $reason,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $contentDigest = null,
    ): string {
        $canonicalMetadata = [
            'record_key' => $recordKey,
            'meaning' => $meaning,
            'signer_id' => $signerId,
            'signed_at' => DateTimeImmutable::createFromInterface($signedAt)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z'),
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ];

        if ($contentDigest !== null) {
            $canonicalMetadata['content_digest'] = $contentDigest;
        }

        return hash('sha256', json_encode(
            $canonicalMetadata,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    public function issueFor(
        User $signer,
        int|string|null $recordKey,
        string $meaning,
        DateTimeInterface $signedAt,
        ?string $reason,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $contentDigest = null,
    ): string {
        $this->authenticator()->assertConfirmed($signer);

        return $this->hashFor(
            recordKey: $recordKey,
            meaning: $meaning,
            signerId: $signer->getAuthIdentifier(),
            signedAt: $signedAt,
            reason: $reason,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            contentDigest: $contentDigest,
        );
    }

    private function authenticator(): ElectronicSignatureAuthenticator
    {
        return $this->authenticator ?? app(ElectronicSignatureAuthenticator::class);
    }
}
