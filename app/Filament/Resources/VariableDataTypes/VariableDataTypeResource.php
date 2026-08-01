<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariableDataTypes;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\LookupResource;
use App\Filament\Resources\VariableDataTypes\Pages\CreateVariableDataType;
use App\Filament\Resources\VariableDataTypes\Pages\EditVariableDataType;
use App\Filament\Resources\VariableDataTypes\Pages\ListVariableDataTypes;
use App\Models\VariableDataType;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VariableDataTypeResource extends LookupResource
{
    protected static ?string $model = VariableDataType::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'System & Security Configuration';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::CheckCircle;

    protected static ?int $navigationSort = 2010;

    public static function getPages(): array
    {
        return [
            'index' => ListVariableDataTypes::route('/'),
            // 'create' => CreateVariableDataType::route('/create'),
            // 'edit' => EditVariableDataType::route('/{record}/edit'),
        ];
    }
}
