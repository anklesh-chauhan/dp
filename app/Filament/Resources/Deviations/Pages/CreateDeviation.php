<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Pages;

use App\Filament\Resources\Deviations\DeviationResource;
use App\Filament\Concerns\AutosavesFormDraft;
use Filament\Resources\Pages\CreateRecord;

final class CreateDeviation extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = DeviationResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.deviations.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reported_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
