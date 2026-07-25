<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface ElectronicSignatureVerifier
{
    public function isValid(ElectronicSignatureRecord $signature): bool;
}
