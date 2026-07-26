<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Pages;

use App\Filament\Resources\Deviations\DeviationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListDeviations extends ListRecords
{
    protected static string $resource = DeviationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
