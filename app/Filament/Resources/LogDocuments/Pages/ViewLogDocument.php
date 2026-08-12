<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments\Pages;

use App\Actions\Sop\SubmitDocumentAction;
use App\Domain\DMS\Actions\LockDocumentAction;
use App\Domain\DMS\Actions\UnlockDocumentAction;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Filament\Support\IssueControlledCopyAction;
use App\Models\DocumentStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewLogDocument extends ViewRecord
{
    use HandlesServiceExceptions;
    use ProvidesControlledDocumentPrintPreviewAction;
    use ProvidesRetentionLifecycleActions;

    protected static string $resource = LogDocumentResource::class;

    public function getSubheading(): ?string
    {
        $status = $this->record->documentStatus?->name ?? 'Unknown status';

        if ($this->record->documentStatus?->hasCode(DocumentStatus::UNDER_REVIEW)) {
            $pending = $this->record->currentPendingApprovalStep();

            if ($pending !== null) {
                return "Under review · Waiting at {$pending->label()}.";
            }

            return 'Under review · Waiting for the next assigned workflow step.';
        }

        return "Status: {$status}";
    }

    protected function getActions(): array
    {
        return [
            ...$this->getDocumentRetentionLifecycleActions(),
            $this->controlledDocumentPrintPreviewAction(),
            Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                    && Auth::user()?->can('submit', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(SubmitDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Submission Failed',
                        successTitle: 'Log document submitted for approval',
                        afterSuccess: fn () => $this->refreshFormData(['document_status_id']),
                    );
                }),
            IssueControlledCopyAction::make(),
            Action::make('lockDocument')
                ->label('Lock for Editing')
                ->icon(Heroicon::LockClosed)
                ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                    && ! $this->record->isLocked()
                    && Auth::user()?->can('lock', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(LockDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Lock Failed',
                        successTitle: 'Document locked',
                    );
                }),
            Action::make('unlockDocument')
                ->label('Unlock')
                ->icon(Heroicon::LockOpen)
                ->color('warning')
                ->visible(fn (): bool => $this->record->isLocked()
                    && Auth::user()?->can('unlock', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(UnlockDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Unlock Failed',
                        successTitle: 'Document unlocked',
                    );
                }),
            EditAction::make()
                ->visible(fn (): bool => Auth::user()?->can('update', $this->record) ?? false),
        ];
    }
}
