<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariableDataTypes;

use App\Filament\Resources\LookupResource;
use App\Filament\Resources\VariableDataTypes\Pages\CreateVariableDataType;
use App\Filament\Resources\VariableDataTypes\Pages\EditVariableDataType;
use App\Filament\Resources\VariableDataTypes\Pages\ListVariableDataTypes;
use App\Models\VariableDataType;

class VariableDataTypeResource extends LookupResource
{
    protected static ?string $model = VariableDataType::class;

    public static function getPages(): array
    {
        return [
            'index' => ListVariableDataTypes::route('/'),
            'create' => CreateVariableDataType::route('/create'),
            'edit' => EditVariableDataType::route('/{record}/edit'),
        ];
    }
}
