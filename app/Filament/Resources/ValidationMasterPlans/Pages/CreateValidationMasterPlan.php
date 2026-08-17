<?php

declare(strict_types=1);

namespace App\Filament\Resources\ValidationMasterPlans\Pages;

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\ValidationMasterPlans\ValidationMasterPlanResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateValidationMasterPlan extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ValidationMasterPlanResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.validation_master_plans.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = ValidationMasterPlanStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
