<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications\Pages;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\EquipmentQualifications\EquipmentQualificationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEquipmentQualification extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = EquipmentQualificationResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.equipment_qualifications.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = EquipmentQualificationStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
