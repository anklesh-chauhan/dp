<?php

namespace App\Filament\Resources\ChangeControls\Pages;

use App\Filament\Resources\ChangeControls\ChangeControlResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChangeControls extends ListRecords
{
    protected static string $resource = ChangeControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
