<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances\Pages;

use App\Filament\Resources\EquipmentMaintenances\EquipmentMaintenanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEquipmentMaintenances extends ListRecords
{
    protected static string $resource = EquipmentMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
