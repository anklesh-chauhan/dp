<?php

declare(strict_types=1);

return [
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
        explode(',', (string) env('DOCUPHARMA_MODULES', 'dms,ai')),
    ))),
];
