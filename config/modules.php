<?php

declare(strict_types=1);

return [
    'entitlement_source' => env('QUALIGXP_ENTITLEMENT_SOURCE', env('DOCUPHARMA_ENTITLEMENT_SOURCE', 'environment')),

    /*
    |--------------------------------------------------------------------------
    | Licensed Product Modules
    |--------------------------------------------------------------------------
    |
    | DMS is the core product. QMS and AI currently depend on DMS. This
    | configuration source can later be replaced by signed license or
    | organization entitlement providers through ModuleManager.
    |
    */
    'enabled' => array_values(array_filter(array_map(
        static fn (string $module): string => strtolower(trim($module)),
        explode(',', (string) env('QUALIGXP_MODULES', env('DOCUPHARMA_MODULES', 'dms,ai'))),
    ))),

    'license' => [
        'public_keys' => array_filter([
            (string) env('QUALIGXP_LICENSE_KEY_ID', env('DOCUPHARMA_LICENSE_KEY_ID', 'default')) => env('QUALIGXP_LICENSE_PUBLIC_KEY', env('DOCUPHARMA_LICENSE_PUBLIC_KEY')),
        ]),
    ],
];
