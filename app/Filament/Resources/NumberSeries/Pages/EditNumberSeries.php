<?php

declare(strict_types=1);

namespace App\Filament\Resources\NumberSeries\Pages;

use App\Filament\Resources\NumberSeries\NumberSeriesResource;
use Filament\Resources\Pages\EditRecord;

class EditNumberSeries extends EditRecord
{
    protected static string $resource = NumberSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
