<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\Pages;

use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\EquipmentAssets\EquipmentAssetResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEquipmentAsset extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = EquipmentAssetResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.equipment_assets.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = EquipmentAssetStatus::Active->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
