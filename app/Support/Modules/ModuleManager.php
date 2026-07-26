<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductModule;
use App\Exceptions\ModuleNotEnabledException;
use App\Support\Modules\Contracts\ModuleEntitlementProvider;

class ModuleManager
{
    public function __construct(
        private readonly ModuleEntitlementProvider $entitlementProvider,
    ) {}

    public function enabled(ProductModule|string $module): bool
    {
        $module = $this->resolve($module);

        if (! in_array($module, $this->entitlementProvider->modules(), true)) {
            return false;
        }

        return collect($module->dependencies())
            ->every(fn (ProductModule $dependency): bool => $this->enabled($dependency));
    }

    public function ensureEnabled(ProductModule|string $module): void
    {
        $module = $this->resolve($module);

        if (! $this->enabled($module)) {
            throw new ModuleNotEnabledException($module);
        }
    }

    /**
     * @return list<ProductModule>
     */
    public function enabledModules(): array
    {
        return array_values(array_filter(
            ProductModule::cases(),
            fn (ProductModule $module): bool => $this->enabled($module),
        ));
    }

    private function resolve(ProductModule|string $module): ProductModule
    {
        if ($module instanceof ProductModule) {
            return $module;
        }

        return ProductModule::tryFrom(strtolower(trim($module)))
            ?? throw new \InvalidArgumentException("Unknown product module [{$module}].");
    }
}
