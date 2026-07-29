<?php

namespace App\Filament\Resources\CsvValidationProjects\Pages;

use App\Filament\Resources\CsvValidationProjects\CsvValidationProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCsvValidationProjects extends ListRecords
{
    protected static string $resource = CsvValidationProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
