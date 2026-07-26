<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\Pages;

use App\Filament\Resources\ChangeControls\ChangeControlResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateChangeControl extends CreateRecord
{
    protected static string $resource = ChangeControlResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();

        return $data;
    }
}
