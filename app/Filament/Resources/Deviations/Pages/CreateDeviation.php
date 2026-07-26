<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Pages;

use App\Filament\Resources\Deviations\DeviationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDeviation extends CreateRecord
{
    protected static string $resource = DeviationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reported_by'] = auth()->id();

        return $data;
    }
}
