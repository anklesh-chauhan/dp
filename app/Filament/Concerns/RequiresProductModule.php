<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Enums\ProductModule;
use App\Support\Modules\ModuleManager;

trait RequiresProductModule
{
    abstract public static function productModule(): ProductModule;

    public static function canView(): bool
    {
        return app(ModuleManager::class)->enabled(static::productModule());
    }
}
