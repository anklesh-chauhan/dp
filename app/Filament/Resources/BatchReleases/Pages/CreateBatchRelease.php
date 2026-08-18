<?php

declare(strict_types=1);

namespace App\Filament\Resources\BatchReleases\Pages;

use App\Filament\Resources\BatchReleases\BatchReleaseResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateBatchRelease extends CreateRecord
{
    protected static string $resource = BatchReleaseResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
