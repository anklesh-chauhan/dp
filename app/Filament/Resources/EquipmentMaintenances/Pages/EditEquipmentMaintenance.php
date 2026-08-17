<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances\Pages;

use App\Filament\Resources\EquipmentMaintenances\EquipmentMaintenanceResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditEquipmentMaintenance extends EditRecord
{
    protected static string $resource = EquipmentMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
