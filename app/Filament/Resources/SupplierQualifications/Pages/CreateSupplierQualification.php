<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierQualifications\Pages;

use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\SupplierQualifications\SupplierQualificationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSupplierQualification extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = SupplierQualificationResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.supplier-qualifications.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = SupplierQualificationStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
