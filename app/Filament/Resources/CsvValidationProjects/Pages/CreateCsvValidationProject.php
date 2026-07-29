<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\Pages;

use App\Filament\Resources\CsvValidationProjects\CsvValidationProjectResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCsvValidationProject extends CreateRecord
{
    protected static string $resource = CsvValidationProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
