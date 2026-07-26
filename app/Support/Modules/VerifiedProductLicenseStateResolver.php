<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\ProductLicenseStateResolver;
use App\Support\Modules\Contracts\SignedLicenseVerifier;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class VerifiedProductLicenseStateResolver implements ProductLicenseStateResolver
{
    public function __construct(
        private readonly SignedLicenseVerifier $verifier,
    ) {}

    public function resolve(
        ProductLicense $license,
        ?DateTimeInterface $at = null,
    ): ProductLicenseState {
        if (! $this->verifier->isValid($license)) {
            return ProductLicenseState::Invalid;
        }

        $evaluatedAt = $at === null
            ? CarbonImmutable::now()
            : CarbonImmutable::instance($at);

        if ($license->revoked_at !== null && ! $license->revoked_at->isAfter($evaluatedAt)) {
            return ProductLicenseState::Revoked;
        }

        if ($license->expires_at === null) {
            return ProductLicenseState::Invalid;
        }

        if (! $evaluatedAt->isAfter($license->expires_at)) {
            return ProductLicenseState::Active;
        }

        if ($license->grace_ends_at !== null && ! $evaluatedAt->isAfter($license->grace_ends_at)) {
            return ProductLicenseState::Grace;
        }

        return ProductLicenseState::Expired;
    }
}
