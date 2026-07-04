<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentStatuses\Pages;

use App\Filament\Resources\DocumentStatuses\DocumentStatusResource;
use App\Filament\Resources\LookupPages\EditLookupRecord;

class EditDocumentStatus extends EditLookupRecord
{
    protected static string $resource = DocumentStatusResource::class;
}
