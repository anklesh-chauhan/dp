<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTypes\Pages;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Filament\Resources\LookupPages\EditLookupRecord;

class EditDocumentType extends EditLookupRecord
{
    protected static string $resource = DocumentTypeResource::class;
}
