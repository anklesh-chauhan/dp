<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentStatuses\Pages;

use App\Filament\Resources\DocumentStatuses\DocumentStatusResource;
use App\Filament\Resources\LookupPages\CreateLookupRecord;

class CreateDocumentStatus extends CreateLookupRecord
{
    protected static string $resource = DocumentStatusResource::class;
}
