<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments\Pages;

use App\Actions\Sop\SubmitDocumentAction;
use App\Domain\DMS\Actions\IssueDocumentAction;
use App\Domain\DMS\Actions\LockDocumentAction;
use App\Domain\DMS\Actions\UnlockDocumentAction;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Models\Department;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
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
            Action::make('issueControlledCopy')
                ->label('Issue Controlled Copy')
                ->icon(Heroicon::DocumentCheck)
                ->color('primary')
                ->schema([
                    Select::make('issuance_type')
                        ->label('Copy type')
                        ->options([
                            DocumentIssuance::TYPE_EXECUTION => 'Writable GMP execution record',
                            DocumentIssuance::TYPE_REFERENCE => 'Read-only reference copy',
                        ])
                        ->default(DocumentIssuance::TYPE_EXECUTION)
                        ->live()
                        ->required(),
                    Select::make('issued_to_user_id')
                        ->label('Issue To User')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    Select::make('issued_to_department_id')
                        ->label('Issue To Department')
                        ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    TextInput::make('issued_to_location')->label('Issue To Location')->maxLength(255),
                    Select::make('log_frequency')
                        ->label('Execution frequency')
                        ->options(['hourly' => 'Hourly', 'shift' => 'Every shift', 'daily' => 'Daily'])
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    DatePicker::make('log_period_start')
                        ->label('Log period start')
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    DatePicker::make('log_period_end')
                        ->label('Log period end')
                        ->afterOrEqual('log_period_start')
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    Select::make('supervisor_id')
                        ->label('Supervisor reviewer')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->requiresSupervisorReview() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->requiresSupervisorReview() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    Textarea::make('notes')->rows(2),
                ])
                ->visible(fn (): bool => $this->record->canBeIssued()
                    && (Auth::user()?->can('Issue:DocumentIssuance') ?? false))
                ->action(function (array $data): void {
                    $this->runServiceAction(
                        fn () => app(IssueDocumentAction::class)->execute($this->record, Auth::user(), $data),
                        failureTitle: 'Issuance Failed',
                        afterSuccess: function ($issuance): void {
                            Notification::make()
                                ->title('Controlled copy issued')
                                ->body("Copy {$issuance->issuance_number} has been issued.")
                                ->success()
                                ->send();
                        },
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
