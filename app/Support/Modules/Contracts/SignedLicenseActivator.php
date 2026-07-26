<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use App\Models\ProductLicense;

interface SignedLicenseActivator
{
    public function activate(string $payload, string $signature, string $keyId): ProductLicense;
}
