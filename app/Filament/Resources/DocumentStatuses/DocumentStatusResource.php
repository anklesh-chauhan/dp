<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentStatuses;

use App\Filament\Resources\DocumentStatuses\Pages\CreateDocumentStatus;
use App\Filament\Resources\DocumentStatuses\Pages\EditDocumentStatus;
use App\Filament\Resources\DocumentStatuses\Pages\ListDocumentStatuses;
use App\Filament\Resources\LookupResource;
use App\Models\DocumentStatus;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DocumentStatusResource extends LookupResource
{
    protected static ?string $model = DocumentStatus::class;

    protected static string|UnitEnum|null $navigationGroup = 'Global Configuration';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentStatuses::route('/'),
            'create' => CreateDocumentStatus::route('/create'),
            'edit' => EditDocumentStatus::route('/{record}/edit'),
        ];
    }
}
