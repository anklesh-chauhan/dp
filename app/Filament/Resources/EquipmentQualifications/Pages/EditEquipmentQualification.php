<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications\Pages;

use App\Filament\Resources\EquipmentQualifications\EquipmentQualificationResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditEquipmentQualification extends EditRecord
{
    protected static string $resource = EquipmentQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
