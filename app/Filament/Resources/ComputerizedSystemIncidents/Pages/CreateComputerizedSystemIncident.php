<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComputerizedSystemIncidents\Pages;

use App\Filament\Resources\ComputerizedSystemIncidents\ComputerizedSystemIncidentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateComputerizedSystemIncident extends CreateRecord
{
    protected static string $resource = ComputerizedSystemIncidentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['detected_at'] ??= now();

        return $data;
    }
}
