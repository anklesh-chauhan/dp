<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use App\Models\ProductLicense;
use DateTimeInterface;

interface ProductLicenseRevoker
{
    public function revoke(
        ProductLicense $license,
        ?DateTimeInterface $revokedAt = null,
    ): ProductLicense;
}
