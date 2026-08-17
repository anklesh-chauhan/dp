<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations\Pages;

use App\Filament\Resources\EquipmentCalibrations\EquipmentCalibrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEquipmentCalibrations extends ListRecords
{
    protected static string $resource = EquipmentCalibrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
