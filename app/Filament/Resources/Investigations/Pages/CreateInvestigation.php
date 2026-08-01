<?php

declare(strict_types=1);

namespace App\Filament\Resources\Investigations\Pages;

use App\Filament\Resources\Investigations\InvestigationResource;
use App\Filament\Concerns\AutosavesFormDraft;
use Filament\Resources\Pages\CreateRecord;

final class CreateInvestigation extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = InvestigationResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.investigations.create';
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
