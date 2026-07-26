<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use DateTimeInterface;

interface ProductLicenseStateResolver
{
    public function resolve(
        ProductLicense $license,
        ?DateTimeInterface $at = null,
    ): ProductLicenseState;
}
