<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopRoles;

use App\Filament\Resources\LookupResource;
use App\Filament\Resources\SopRoles\Pages\CreateSopRole;
use App\Filament\Resources\SopRoles\Pages\EditSopRole;
use App\Filament\Resources\SopRoles\Pages\ListSopRoles;
use App\Models\SopRole;

class SopRoleResource extends LookupResource
{
    protected static ?string $model = SopRole::class;

    public static function getPages(): array
    {
        return [
            'index' => ListSopRoles::route('/'),
            'create' => CreateSopRole::route('/create'),
            'edit' => EditSopRole::route('/{record}/edit'),
        ];
    }
}
