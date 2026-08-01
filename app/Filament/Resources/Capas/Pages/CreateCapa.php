<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Pages;

use App\Filament\Resources\Capas\CapaResource;
use App\Filament\Concerns\AutosavesFormDraft;
use Filament\Resources\Pages\CreateRecord;

final class CreateCapa extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = CapaResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.capas.create';
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
