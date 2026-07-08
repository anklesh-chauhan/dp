<?php

declare(strict_types=1);

namespace App\Filament\Resources\NumberSeries\Pages;

use App\Filament\Pages\ManageNumberSeriesSettings;
use App\Filament\Resources\NumberSeries\NumberSeriesResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListNumberSeries extends ListRecords
{
    protected static string $resource = NumberSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('defaults')
                ->label('Default Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(ManageNumberSeriesSettings::getUrl()),
        ];
    }
}
