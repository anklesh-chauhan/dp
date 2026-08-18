<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

final class ElectronicSignatureContentDigester
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function digest(array $payload): string
    {
        $canonical = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return hash('sha256', $canonical);
    }
}
