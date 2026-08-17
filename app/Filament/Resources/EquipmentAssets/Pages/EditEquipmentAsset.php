<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\Pages;

use App\Filament\Resources\EquipmentAssets\EquipmentAssetResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditEquipmentAsset extends EditRecord
{
    protected static string $resource = EquipmentAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
