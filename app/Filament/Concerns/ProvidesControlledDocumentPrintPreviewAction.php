<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\ControlledDocument;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

trait ProvidesControlledDocumentPrintPreviewAction
{
    protected function controlledDocumentPrintPreviewAction(
        string $name = 'previewWithPrintTemplate',
    ): Action {
        return Action::make($name)
            ->label('Print Preview')
            ->icon(Heroicon::Eye)
            ->color('gray')
            ->tooltip('Opens the print layout for review. This is not controlled printing.')
            ->url(fn (): string => route('controlled-documents.draft-preview', $this->record))
            ->openUrlInNewTab()
            ->visible(fn (): bool => $this->record instanceof ControlledDocument
                && $this->record->canPreviewWithPrintTemplate(Auth::user()));
    }

    protected function controlledDocumentPrintPreviewModalAction(
        ControlledDocument $document,
        string $name = 'openPrintPreview',
    ): ?Action {
        if (! $document->canPreviewWithPrintTemplate(Auth::user())) {
            return null;
        }

        return Action::make($name)
            ->label('Print Preview')
            ->icon(Heroicon::Eye)
            ->color('gray')
            ->url(route('controlled-documents.draft-preview', $document))
            ->openUrlInNewTab();
    }
}
