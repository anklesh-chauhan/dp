<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemplateStatuses;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\LookupResource;
use App\Filament\Resources\TemplateStatuses\Pages\CreateTemplateStatus;
use App\Filament\Resources\TemplateStatuses\Pages\EditTemplateStatus;
use App\Filament\Resources\TemplateStatuses\Pages\ListTemplateStatuses;
use App\Models\TemplateStatus;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TemplateStatusResource extends LookupResource
{
    protected static ?string $model = TemplateStatus::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'General Masters';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::CheckCircle;

    protected static ?int $navigationSort = 1009;

    public static function getPages(): array
    {
        return [
            'index' => ListTemplateStatuses::route('/'),
            //'create' => CreateTemplateStatus::route('/create'),
            //'edit' => EditTemplateStatus::route('/{record}/edit'),
        ];
    }
}
