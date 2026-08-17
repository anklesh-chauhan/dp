<?php

declare(strict_types=1);

namespace App\Filament\Resources\Complaints\Pages;

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\Complaints\ComplaintResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateComplaint extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ComplaintResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.complaints.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['received_by'] = auth()->id();
        $data['status'] = ComplaintStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
