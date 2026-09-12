<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\Pages;

use App\Actions\Sop\SubmitDocumentAction;
use App\Domain\DMS\Services\ControlledDocumentSectionReviewService;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\DocumentStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class EditControlledDocument extends EditRecord
{
    use HandlesServiceExceptions;
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
            Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Start document approval workflow?')
                ->modalDescription(function (): string {
                    $description = 'The document will be locked for editing and sent to the first eligible reviewer. You can follow each signed decision in Approval History.';
                    $attention = $this->record->sectionReviewAttentionSummary();

                    if ($attention === null) {
                        return $description;
                    }

                    return $description.' '.$attention.' Mark those comments as addressed before submitting.';
                })
                ->modalSubmitActionLabel('Submit for approval')
                ->disabled(fn (): bool => $this->record->hasOpenSectionReviewComments())
                ->tooltip(fn (): ?string => $this->record->hasOpenSectionReviewComments()
                    ? ControlledDocumentSectionReviewService::UNRESOLVED_COMMENTS_SUBMISSION_MESSAGE
                    : null)
                ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                    && Auth::user()?->can('submit', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(SubmitDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Submission Failed',
                        successTitle: 'Document submitted for approval',
                        successBody: 'The document is locked and the first actionable step is now available in the assigned reviewer’s approval queue.',
                        afterSuccess: fn () => $this->refreshFormData(['document_status_id', 'approvals']),
                    );
                }),
            $this->controlledDocumentPrintPreviewAction(),
            $this->controlledDocumentDirectPrintAction(),
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
