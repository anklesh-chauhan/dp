<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductModule;
use App\Support\Modules\Contracts\ModuleEntitlementProvider;
use Illuminate\Contracts\Config\Repository;

class ConfiguredModuleEntitlementProvider implements ModuleEntitlementProvider
{
    public function __construct(private readonly Repository $config) {}

    public function modules(): array
    {
        return collect($this->config->get('modules.enabled', []))
            ->map(fn (mixed $module): ?ProductModule => $module instanceof ProductModule
                ? $module
                : ProductModule::tryFrom(strtolower(trim((string) $module)))
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
