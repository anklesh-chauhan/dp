<?php

namespace App\Filament\Resources\CsvValidationProjects\Pages;

use App\Filament\Resources\CsvValidationProjects\CsvValidationProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCsvValidationProject extends EditRecord
{
    protected static string $resource = CsvValidationProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
