<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductLicenseState;
use App\Enums\ProductModule;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\LicenseLifecycleEvaluator;
use App\Support\Modules\Contracts\ModuleEntitlementProvider;
use JsonException;

class SignedLicenseEntitlementProvider implements ModuleEntitlementProvider
{
    public function __construct(
        private readonly LicenseLifecycleEvaluator $lifecycleEvaluator,
    ) {}

    public function modules(): array
    {
        $licenses = ProductLicense::query()
            ->orderByDesc('issued_at')
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->get();

        foreach ($licenses as $license) {
            if (! in_array(
                $this->lifecycleEvaluator->evaluate($license),
                [ProductLicenseState::Active, ProductLicenseState::Grace],
                true,
            )) {
                continue;
            }

            $modules = $this->modulesFrom($license);

            if ($modules !== []) {
                return $modules;
            }
        }

        return [];
    }

    /**
     * @return list<ProductModule>
     */
    private function modulesFrom(ProductLicense $license): array
    {
        try {
            $claims = json_decode($license->payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        $moduleClaims = is_array($claims) ? ($claims['modules'] ?? null) : null;

        if (! is_array($moduleClaims) || ! array_is_list($moduleClaims) || $moduleClaims === []) {
            return [];
        }

        $modules = [];

        foreach ($moduleClaims as $moduleClaim) {
            if (! is_string($moduleClaim)) {
                return [];
            }

            $module = ProductModule::tryFrom($moduleClaim);

            if ($module === null || in_array($module, $modules, true)) {
                return [];
            }

            $modules[] = $module;
        }

        foreach ($modules as $module) {
            foreach ($module->dependencies() as $dependency) {
                if (! in_array($dependency, $modules, true)) {
                    return [];
                }
            }
        }

        return $modules;
    }
}
