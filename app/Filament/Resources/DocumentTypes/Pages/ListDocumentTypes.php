<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTypes\Pages;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Filament\Resources\LookupPages\ListLookupRecords;

class ListDocumentTypes extends ListLookupRecords
{
    protected static string $resource = DocumentTypeResource::class;
}
