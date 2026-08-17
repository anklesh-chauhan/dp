<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications\Pages;

use App\Filament\Resources\EquipmentQualifications\EquipmentQualificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEquipmentQualifications extends ListRecords
{
    protected static string $resource = EquipmentQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
