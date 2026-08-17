<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations\Pages;

use App\Filament\Resources\EquipmentCalibrations\EquipmentCalibrationResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditEquipmentCalibration extends EditRecord
{
    protected static string $resource = EquipmentCalibrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
