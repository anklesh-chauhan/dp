<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopRoles;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\LookupResource;
use App\Filament\Resources\SopRoles\Pages\CreateSopRole;
use App\Filament\Resources\SopRoles\Pages\EditSopRole;
use App\Filament\Resources\SopRoles\Pages\ListSopRoles;
use App\Models\SopRole;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SopRoleResource extends LookupResource
{
    protected static ?string $model = SopRole::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'DMS Configuration';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?int $navigationSort = 1008;

    public static function getPages(): array
    {
        return [
            'index' => ListSopRoles::route('/'),
            // 'create' => CreateSopRole::route('/create'),
            // 'edit' => EditSopRole::route('/{record}/edit'),
        ];
    }
}
