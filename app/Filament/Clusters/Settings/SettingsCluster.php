<?php

namespace App\Filament\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'General Settings';

    // 1. Assign it to a "Settings" group
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    // 2. Give it a high sort order inside that group
    protected static ?int $navigationSort = 1000;

}
