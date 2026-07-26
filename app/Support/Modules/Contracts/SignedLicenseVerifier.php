<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use App\Models\ProductLicense;

interface SignedLicenseVerifier
{
    public function isValid(ProductLicense $license): bool;
}
