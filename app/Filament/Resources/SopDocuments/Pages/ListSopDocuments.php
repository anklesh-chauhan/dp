<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\Pages;

use App\Filament\Resources\SopDocuments\SopDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSopDocuments extends ListRecords
{
    protected static string $resource = SopDocumentResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
