<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface ProvidesElectronicSignatureContent
{
    /**
     * @return array<string, mixed>
     */
    public function electronicSignatureContentPayload(): array;
}
