<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances\Pages;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\EquipmentMaintenances\EquipmentMaintenanceResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEquipmentMaintenance extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = EquipmentMaintenanceResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.equipment_maintenances.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = EquipmentMaintenanceStatus::Planned->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
