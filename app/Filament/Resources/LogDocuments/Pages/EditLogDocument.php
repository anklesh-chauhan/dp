<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments\Pages;

use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditLogDocument extends EditRecord
{
    use ProvidesControlledDocumentPrintPreviewAction;

    protected static string $resource = LogDocumentResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! Auth::user()?->can('update', $this->record)) {
            $this->redirect(LogDocumentResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getActions(): array
    {
        return [
            $this->controlledDocumentPrintPreviewAction(),
        ];
    }
}
