<?php

declare(strict_types=1);

namespace App\Domain\QMS\Concerns;

trait StoresSignatureContentDigest
{
    public function signatureContentDigest(): ?string
    {
        if (($this->signature_hash ?? null) === null) {
            return null;
        }

        $context = $this->context;

        if (! is_array($context)) {
            return null;
        }

        $digest = $context['content_digest'] ?? null;

        return is_string($digest) && $digest !== '' ? $digest : null;
    }
}
