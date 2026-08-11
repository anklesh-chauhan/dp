<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\Pages;

use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class EditControlledDocument extends EditRecord
{
    use ProvidesControlledDocumentPrintPreviewAction;

    protected static string $resource = ControlledDocumentResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! Auth::user()?->can('update', $this->record)) {
            Notification::make()
                ->title('Document is locked or not editable')
                ->body('Only draft documents that are not locked by another user can be edited.')
                ->danger()
                ->send();

            $this->redirect(ControlledDocumentResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getActions(): array
    {
        return [
            $this->controlledDocumentPrintPreviewAction(),
            Action::make('printPdf')
                ->label('View PDF')
                ->icon(Heroicon::Eye)
                ->url(fn (): string => route('controlled-documents.viewer', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->canBePrintedDirectly()),
            DeleteAction::make(),
        ];
    }
}
