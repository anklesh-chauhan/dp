<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemplateStatuses;

use App\Filament\Resources\LookupResource;
use App\Filament\Resources\TemplateStatuses\Pages\CreateTemplateStatus;
use App\Filament\Resources\TemplateStatuses\Pages\EditTemplateStatus;
use App\Filament\Resources\TemplateStatuses\Pages\ListTemplateStatuses;
use App\Models\TemplateStatus;

class TemplateStatusResource extends LookupResource
{
    protected static ?string $model = TemplateStatus::class;

    public static function getPages(): array
    {
        return [
            'index' => ListTemplateStatuses::route('/'),
            'create' => CreateTemplateStatus::route('/create'),
            'edit' => EditTemplateStatus::route('/{record}/edit'),
        ];
    }
}
