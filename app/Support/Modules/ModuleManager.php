<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductModule;
use App\Exceptions\ModuleNotEnabledException;
use Illuminate\Contracts\Config\Repository;

class ModuleManager
{
    public function __construct(private readonly Repository $config) {}

    public function enabled(ProductModule|string $module): bool
    {
        $module = $this->resolve($module);

        if (! in_array($module, $this->configuredModules(), true)) {
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

    /**
     * @return list<ProductModule>
     */
    private function configuredModules(): array
    {
        return collect($this->config->get('modules.enabled', []))
            ->map(fn (mixed $module): ?ProductModule => $module instanceof ProductModule
                ? $module
                : ProductModule::tryFrom(strtolower(trim((string) $module)))
            )
            ->filter()
            ->values()
            ->all();
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
