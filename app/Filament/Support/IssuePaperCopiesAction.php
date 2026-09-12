<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\DMS\Actions\IssueDocumentAction;
use App\Domain\DMS\Services\IssuancePrintPackService;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IssuePaperCopiesAction
{
    public static function make(): Action
    {
        return Action::make('issuePaperCopies')
            ->label('Print and fill on paper')
            ->icon(Heroicon::Printer)
            ->color('gray')
            ->schema(fn (): array => self::schema())
            ->visible(fn (ControlledDocument $record): bool => $record->canBeIssued()
                && (Auth::user()?->can('Issue:DocumentIssuance') ?? false))
            ->action(function (ControlledDocument $record, array $data, Component $livewire): void {
                ServiceExceptionHandler::run(
                    function () use ($record, $data): array {
                        $issuances = app(IssueDocumentAction::class)->execute($record, Auth::user(), [
                            ...$data,
                            'issuance_type' => DocumentIssuance::TYPE_PAPER,
                        ]);

                        $first = $issuances->first();
                        $batch = $first instanceof DocumentIssuance
                            ? DocumentIssuanceBatch::query()->find($first->issuance_batch_id)
                            : null;

                        if ($batch instanceof DocumentIssuanceBatch) {
                            $result = app(IssuancePrintPackService::class)->request($batch, Auth::user());
                            $batch = $result['batch'];
                        }

                        return [
                            'issuances' => $issuances,
                            'batch' => $batch,
                        ];
                    },
                    failureTitle: 'Paper issuance failed',
                    afterSuccess: function (array $result) use ($livewire): void {
                        $issuances = $result['issuances'];
                        $batch = $result['batch'];
                        $first = $issuances->first();
                        $last = $issuances->last();
                        $count = $issuances->count();

                        if (! $first instanceof DocumentIssuance || ! $last instanceof DocumentIssuance) {
                            return;
                        }

                        $range = $count === 1
                            ? $first->issuance_number
                            : "{$first->issuance_number}–{$last->issuance_number}";

                        Notification::make()
                            ->title($count === 1 ? 'Paper copy issued' : 'Paper copies issued')
                            ->body($count === 1
                                ? "Copy {$range} is ready to print."
                                : "{$count} paper copies issued ({$range}). Print is opening.")
                            ->success()
                            ->send();

                        if ($batch instanceof DocumentIssuanceBatch) {
                            DirectPrint::open($livewire, DirectPrint::batchUrl($batch));
                        }
                    },
                );
            });
    }

    /**
     * @return array<int, Select|TextInput|Textarea>
     */
    private static function schema(): array
    {
        return [
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
            TextInput::make('copy_count')
                ->label('Number of copies')
                ->helperText('Each copy gets its own number. After you confirm, Print opens so you can send the pages to the printer.')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(DocumentIssuanceBatch::MAX_PAPER_COPIES)
                ->default(1)
                ->required(),
            Textarea::make('notes')->rows(2),
        ];
    }
}
