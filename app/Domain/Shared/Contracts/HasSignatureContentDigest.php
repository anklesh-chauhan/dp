<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface HasSignatureContentDigest
{
    public function signatureContentDigest(): ?string;
}
