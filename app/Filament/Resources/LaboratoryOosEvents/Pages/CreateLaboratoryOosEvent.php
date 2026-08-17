<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents\Pages;

use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\LaboratoryOosEvents\LaboratoryOosEventResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLaboratoryOosEvent extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = LaboratoryOosEventResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.laboratory_oos_events.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = LaboratoryOosStatus::Draft->value;
        $data['phase_one_outcome'] ??= LaboratoryOosPhaseOutcome::Pending->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
