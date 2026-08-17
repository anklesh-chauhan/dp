<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\Pages;

use App\Filament\Resources\EquipmentAssets\EquipmentAssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEquipmentAssets extends ListRecords
{
    protected static string $resource = EquipmentAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
