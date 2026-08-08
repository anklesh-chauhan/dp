<?php

namespace App\Filament\Resources\DocumentExecutions\Pages;

use App\Filament\Resources\DocumentExecutions\DocumentExecutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentExecutions extends ListRecords
{
    protected static string $resource = DocumentExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
