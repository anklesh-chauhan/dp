<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\Pages;

use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\User;
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

        $user = Auth::user();

        if (
            ! $user instanceof User
            || ! $user->can('update', $this->record)
            || ! $this->record->canBeEditedBy($user)
        ) {
            Notification::make()
                ->title('Document is not editable')
                ->body('Only unlocked draft documents can be edited. Effective and approved documents require a new revision.')
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
