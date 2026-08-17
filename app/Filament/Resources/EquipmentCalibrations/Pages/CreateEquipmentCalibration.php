<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations\Pages;

use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\EquipmentCalibrations\EquipmentCalibrationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEquipmentCalibration extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = EquipmentCalibrationResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.equipment_calibrations.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = EquipmentCalibrationStatus::Scheduled->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
