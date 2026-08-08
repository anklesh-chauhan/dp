<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentExecutions\Pages;

use App\Filament\Concerns\ProvidesDocumentExecutionActions;
use App\Filament\Resources\DocumentExecutions\DocumentExecutionResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentExecution extends EditRecord
{
    use ProvidesDocumentExecutionActions;

    protected static string $resource = DocumentExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getDocumentExecutionActions(),
            ViewAction::make(),
        ];
    }
}
