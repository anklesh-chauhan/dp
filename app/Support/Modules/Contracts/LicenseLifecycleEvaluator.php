<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use DateTimeInterface;

interface LicenseLifecycleEvaluator
{
    public function evaluate(
        ProductLicense $license,
        ?DateTimeInterface $at = null,
    ): ProductLicenseState;
}
