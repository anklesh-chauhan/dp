<?php

declare(strict_types=1);

namespace App\Filament\Resources\IssuanceStatuses;

use App\Filament\Clusters\Settings\SettingsCluster;
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

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'General Masters';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    protected static ?int $navigationSort = 1007;

    public static function getPages(): array
    {
        return [
            'index' => ListIssuanceStatuses::route('/'),
            //'create' => CreateIssuanceStatus::route('/create'),
            //'edit' => EditIssuanceStatus::route('/{record}/edit'),
        ];
    }
}
