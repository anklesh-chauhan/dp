<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\Pages;

use App\Filament\Resources\SiteMasterFiles\SiteMasterFileResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSiteMasterFile extends CreateRecord
{
    protected static string $resource = SiteMasterFileResource::class;

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
