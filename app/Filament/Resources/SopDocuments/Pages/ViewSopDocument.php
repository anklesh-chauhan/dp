<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\Pages;

use App\Actions\Sop\SubmitDocumentAction;
use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Domain\DMS\Actions\LockDocumentAction;
use App\Domain\DMS\Actions\UnlockDocumentAction;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use App\Models\DocumentStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewSopDocument extends ViewRecord
{
    use HandlesServiceExceptions;
    use ProvidesRetentionLifecycleActions;

    protected static string $resource = SopDocumentResource::class;

    protected function getActions(): array
    {
        return [
            ...$this->getDocumentRetentionLifecycleActions(),
            Action::make('createRevision')
                ->label('Create Revision')
                ->icon(Heroicon::DocumentDuplicate)
                ->color('warning')
                ->schema([
                    Textarea::make('revision_reason')
                        ->label('Reason for revision')
                        ->required()
                        ->maxLength(2000),
                ])
                ->visible(fn (): bool => in_array($this->record->documentStatus?->code, [
                    DocumentStatus::APPROVED,
                    DocumentStatus::EFFECTIVE,
                    DocumentStatus::OBSOLETE,
                ], true) && (Auth::user()?->can('revise', $this->record) ?? false))
                ->action(function (array $data): void {
                    $this->runServiceAction(
                        fn () => app(CreateDocumentRevisionAction::class)->execute(
                            $this->record,
                            Auth::user(),
                            $data['revision_reason'],
                        ),
                        failureTitle: 'Revision Failed',
                        successTitle: 'Draft revision created',
                        afterSuccess: fn ($revision) => $this->redirect(
                            SopDocumentResource::getUrl('edit', ['record' => $revision])
                        ),
                    );
                }),
            Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Submit this document to the department approval workflow. Editing will be locked once submitted.')
                ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                    && Auth::user()?->can('submit', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(SubmitDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Submission Failed',
                        successTitle: 'Document submitted for approval',
                        afterSuccess: fn () => $this->refreshFormData(['document_status_id', 'approvals']),
                    );
                }),
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
                        successTitle: 'Document locked for editing',
                        afterSuccess: fn () => $this->refreshFormData(['locked_by', 'locked_at']),
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
                        afterSuccess: fn () => $this->refreshFormData(['locked_by', 'locked_at']),
                    );
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
