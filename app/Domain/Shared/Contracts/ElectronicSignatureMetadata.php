<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use DateTimeInterface;

interface ElectronicSignatureMetadata
{
    public function signatureMeaning(): ?string;

    public function signatureSignerId(): int|string|null;

    public function signatureTimestamp(): ?DateTimeInterface;

    public function signatureHash(): ?string;

    public function signatureReason(): ?string;

    public function signatureIpAddress(): ?string;

    public function signatureUserAgent(): ?string;
}
