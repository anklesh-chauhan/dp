<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentIssuances\Pages;

use App\Filament\Resources\DocumentIssuances\DocumentIssuanceResource;
use Filament\Resources\Pages\ListRecords;

class ListDocumentIssuances extends ListRecords
{
    protected static string $resource = DocumentIssuanceResource::class;
}
