<?php

namespace App\Filament\Resources\RegulationTags\Pages;

use App\Filament\Resources\RegulationTags\RegulationTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRegulationTags extends ManageRecords
{
    protected static string $resource = RegulationTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
