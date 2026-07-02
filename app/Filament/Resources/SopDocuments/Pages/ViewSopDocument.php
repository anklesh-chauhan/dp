<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\Pages;

use App\Actions\Sop\LockDocumentAction;
use App\Actions\Sop\SubmitDocumentAction;
use App\Actions\Sop\UnlockDocumentAction;
use App\Enums\DocumentStatus;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewSopDocument extends ViewRecord
{
    protected static string $resource = SopDocumentResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Submit this document to the department approval workflow. Editing will be locked once submitted.')
                ->visible(fn (): bool => $this->record->status === DocumentStatus::Draft
                    && Auth::user()?->can('submit', $this->record))
                ->action(function (): void {
                    app(SubmitDocumentAction::class)->execute($this->record, Auth::user());

                    Notification::make()
                        ->title('Document submitted for approval')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'approvals']);
                }),
            Action::make('lockDocument')
                ->label('Lock for Editing')
                ->icon(Heroicon::LockClosed)
                ->visible(fn (): bool => $this->record->status === DocumentStatus::Draft
                    && ! $this->record->isLocked()
                    && Auth::user()?->can('lock', $this->record))
                ->action(function (): void {
                    app(LockDocumentAction::class)->execute($this->record, Auth::user());

                    Notification::make()
                        ->title('Document locked for editing')
                        ->success()
                        ->send();

                    $this->refreshFormData(['locked_by', 'locked_at']);
                }),
            Action::make('unlockDocument')
                ->label('Unlock')
                ->icon(Heroicon::LockOpen)
                ->color('warning')
                ->visible(fn (): bool => $this->record->isLocked()
                    && Auth::user()?->can('unlock', $this->record))
                ->action(function (): void {
                    app(UnlockDocumentAction::class)->execute($this->record, Auth::user());

                    Notification::make()
                        ->title('Document unlocked')
                        ->success()
                        ->send();

                    $this->refreshFormData(['locked_by', 'locked_at']);
                }),
            Action::make('printPdf')
                ->label('Print / PDF')
                ->icon(Heroicon::Printer)
                ->url(fn (): string => route('sop-documents.print', $this->record))
                ->openUrlInNewTab(),
            EditAction::make()
                ->visible(fn (): bool => Auth::user()?->can('update', $this->record) ?? false),
        ];
    }
}
