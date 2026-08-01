<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\Pages;

use App\Filament\Resources\ChangeControls\ChangeControlResource;
use App\Filament\Concerns\AutosavesFormDraft;
use Filament\Resources\Pages\CreateRecord;

final class CreateChangeControl extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ChangeControlResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.change-controls.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
