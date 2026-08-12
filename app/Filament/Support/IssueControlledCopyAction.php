<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\DMS\Actions\IssueDocumentAction;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentIssuance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class IssueControlledCopyAction
{
    public static function make(): Action
    {
        return Action::make('issueControlledCopy')
            ->label('Issue Controlled Copy')
            ->icon(Heroicon::DocumentCheck)
            ->color('primary')
            ->schema(fn (ControlledDocument $record): array => self::schema($record))
            ->visible(fn (ControlledDocument $record): bool => $record->canBeIssued()
                && (Auth::user()?->can('Issue:DocumentIssuance') ?? false))
            ->action(function (ControlledDocument $record, array $data): void {
                ServiceExceptionHandler::run(
                    fn () => app(IssueDocumentAction::class)->execute($record, Auth::user(), $data),
                    failureTitle: 'Issuance Failed',
                    afterSuccess: function (DocumentIssuance $issuance): void {
                        Notification::make()
                            ->title('Controlled copy issued')
                            ->body("Copy {$issuance->issuance_number} has been issued.")
                            ->success()
                            ->send();
                    },
                );
            });
    }

    /**
     * @return array<int, Select|TextInput|DatePicker|Textarea>
     */
    private static function schema(ControlledDocument $document): array
    {
        $requiresExecution = $document->documentType?->requiresExecutionRecord() ?? false;
        $isRepeatingLog = $document->documentType?->isRepeatingLog() ?? false;
        $isBatchRecord = $document->documentType?->isBatchRecord() ?? false;
        $requiresSupervisor = $document->documentType?->requiresSupervisorReview() ?? false;
        $isExecutionCopy = fn (Get $get): bool => $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION;

        return [
            Select::make('issuance_type')
                ->label('Copy type')
                ->options($requiresExecution
                    ? [
                        DocumentIssuance::TYPE_EXECUTION => 'Writable GMP execution record',
                        DocumentIssuance::TYPE_REFERENCE => 'Read-only reference copy',
                    ]
                    : [DocumentIssuance::TYPE_REFERENCE => 'Read-only reference copy'])
                ->default($requiresExecution
                    ? DocumentIssuance::TYPE_EXECUTION
                    : DocumentIssuance::TYPE_REFERENCE)
                ->live()
                ->required(),
            Select::make('issued_to_user_id')
                ->label('Issue To User')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->requiredWithout('issued_to_department_id'),
            Select::make('issued_to_department_id')
                ->label('Issue To Department')
                ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->requiredWithout('issued_to_user_id'),
            TextInput::make('issued_to_location')->label('Issue To Location')->maxLength(255),
            TextInput::make('batch_number')
                ->visible(fn (Get $get): bool => $isBatchRecord && $isExecutionCopy($get)),
            TextInput::make('product_name')
                ->visible(fn (Get $get): bool => $isBatchRecord && $isExecutionCopy($get)),
            Select::make('log_frequency')
                ->label('Execution frequency')
                ->options(['hourly' => 'Hourly', 'shift' => 'Every shift', 'daily' => 'Daily'])
                ->visible(fn (Get $get): bool => $isRepeatingLog && $isExecutionCopy($get))
                ->required(fn (Get $get): bool => $isRepeatingLog && $isExecutionCopy($get)),
            DatePicker::make('log_period_start')
                ->label('Log period start')
                ->visible(fn (Get $get): bool => $isRepeatingLog && $isExecutionCopy($get))
                ->required(fn (Get $get): bool => $isRepeatingLog && $isExecutionCopy($get)),
            DatePicker::make('log_period_end')
                ->label('Log period end')
                ->afterOrEqual('log_period_start')
                ->visible(fn (Get $get): bool => $isRepeatingLog && $isExecutionCopy($get))
                ->required(fn (Get $get): bool => $isRepeatingLog && $isExecutionCopy($get)),
            Select::make('supervisor_id')
                ->label('Supervisor reviewer')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->visible(fn (Get $get): bool => $requiresSupervisor && $isExecutionCopy($get))
                ->required(fn (Get $get): bool => $requiresSupervisor && $isExecutionCopy($get)),
            Textarea::make('notes')->rows(2),
        ];
    }
}
