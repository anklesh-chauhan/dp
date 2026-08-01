<?php

namespace App\Filament\Clusters\Settings;

use App\Enums\ProductModule;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'DMS Settings';

    protected static string|UnitEnum|null $navigationGroup = 'DMS · Settings';

    protected static ?int $navigationSort = 10000;

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::DMS);
    }
}
