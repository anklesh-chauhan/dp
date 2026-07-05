<?php

declare(strict_types=1);

namespace App\Filament\Resources\IssuanceStatuses;

use App\Filament\Resources\IssuanceStatuses\Pages\CreateIssuanceStatus;
use App\Filament\Resources\IssuanceStatuses\Pages\EditIssuanceStatus;
use App\Filament\Resources\IssuanceStatuses\Pages\ListIssuanceStatuses;
use App\Filament\Resources\LookupResource;
use App\Models\IssuanceStatus;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class IssuanceStatusResource extends LookupResource
{
    protected static ?string $model = IssuanceStatus::class;

    protected static string|UnitEnum|null $navigationGroup = 'Global Configuration';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    public static function getPages(): array
    {
        return [
            'index' => ListIssuanceStatuses::route('/'),
            //'create' => CreateIssuanceStatus::route('/create'),
            //'edit' => EditIssuanceStatus::route('/{record}/edit'),
        ];
    }
}
