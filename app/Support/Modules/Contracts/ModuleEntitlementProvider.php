<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use App\Enums\ProductModule;

interface ModuleEntitlementProvider
{
    /**
     * @return list<ProductModule>
     */
    public function modules(): array;
}
