<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentExecutions\Pages;

use App\Filament\Concerns\ProvidesDocumentExecutionActions;
use App\Filament\Resources\DocumentExecutions\DocumentExecutionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentExecution extends ViewRecord
{
    use ProvidesDocumentExecutionActions;

    protected static string $resource = DocumentExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getDocumentExecutionActions(),
            EditAction::make()->visible(fn (): bool => $this->record->isEditable()),
        ];
    }
}
