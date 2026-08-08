<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Domain\DMS\Services\DocumentExecutionService;
use App\Models\DocumentExecution;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait ProvidesDocumentExecutionActions
{
    /** @return array<int, Action> */
    protected function getDocumentExecutionActions(): array
    {
        return [
            Action::make('beginExecution')
                ->label('Begin execution')
                ->icon(Heroicon::Play)
                ->visible(fn (): bool => $this->record->status === DocumentExecution::STATUS_ISSUED
                    && (Auth::user()?->can('update', $this->record) ?? false))
                ->action(fn () => $this->replaceRecord(
                    app(DocumentExecutionService::class)->begin($this->record),
                    'Execution started.',
                )),
            Action::make('completeExecution')
                ->label('Complete and submit')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, [DocumentExecution::STATUS_ISSUED, DocumentExecution::STATUS_IN_PROGRESS], true)
                    && (Auth::user()?->can('submit', $this->record) ?? false))
                ->action(function (Action $action): void {
                    try {
                        $record = app(DocumentExecutionService::class)->complete($this->record, Auth::user());
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Execution submission blocked')
                            ->body(collect($exception->errors())
                                ->flatten()
                                ->unique()
                                ->implode("\n"))
                            ->persistent()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $this->replaceRecord(
                        $record,
                        'Execution submitted for the required review.',
                    );
                }),
            Action::make('supervisorReview')
                ->label('Complete supervisor review')
                ->icon(Heroicon::UserCircle)
                ->schema([Textarea::make('review_notes')->rows(3)])
                ->visible(fn (): bool => $this->record->status === DocumentExecution::STATUS_UNDER_REVIEW
                    && (Auth::user()?->can('review', $this->record) ?? false))
                ->action(fn (array $data) => $this->replaceRecord(
                    app(DocumentExecutionService::class)->review($this->record, Auth::user(), $data['review_notes'] ?? null),
                    'Supervisor review completed.',
                )),
            Action::make('qaDisposition')
                ->label('QA disposition')
                ->icon(Heroicon::ShieldCheck)
                ->color('primary')
                ->schema([
                    Select::make('disposition')
                        ->options([
                            DocumentExecution::DISPOSITION_RELEASED => 'Release batch',
                            DocumentExecution::DISPOSITION_REJECTED => 'Reject batch',
                        ])
                        ->required(),
                    Textarea::make('qa_notes')->rows(3),
                ])
                ->visible(fn (): bool => $this->record->status === DocumentExecution::STATUS_QA_REVIEW
                    && (Auth::user()?->can('approve', $this->record) ?? false))
                ->action(function (array $data, Action $action): void {
                    try {
                        $record = app(DocumentExecutionService::class)->qaApprove(
                            $this->record,
                            Auth::user(),
                            $data['disposition'],
                            $data['qa_notes'] ?? null,
                        );
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('QA disposition blocked')
                            ->body(collect($exception->errors())
                                ->flatten()
                                ->unique()
                                ->implode("\n"))
                            ->persistent()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $this->replaceRecord(
                        $record,
                        'QA disposition recorded and execution closed.',
                    );
                }),
        ];
    }

    private function replaceRecord(DocumentExecution $record, string $message): void
    {
        $this->record = $record;

        Notification::make()->title($message)->success()->send();
    }
}
