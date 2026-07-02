<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments\Pages;

use App\Filament\Resources\LogDocuments\LogDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListLogDocuments extends ListRecords
{
    protected static string $resource = LogDocumentResource::class;
}
