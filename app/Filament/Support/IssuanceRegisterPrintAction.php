<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\DMS\Services\IssuancePrintPackService;
use App\Filament\Resources\DocumentIssuances\DocumentIssuanceResource;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class IssuanceRegisterPrintAction
{
    public static function byIssuanceNumber(): Action
    {
        return Action::make('printByIssuanceNumber')
            ->label('Print by copy number')
            ->icon(Heroicon::Printer)
            ->visible(fn (): bool => Auth::user()?->can('PrintPdf:ControlledDocument') ?? false)
            ->schema([
                Textarea::make('issuance_numbers')
                    ->label('Copy numbers')
                    ->helperText('Example: SOP-QA-00001-C01, SOP-QA-00001-C02 or range SOP-QA-00001-C01-SOP-QA-00001-C10. Copies must be from the same document.')
                    ->rows(6)
                    ->required(),
            ])
            ->action(function (array $data, Component $livewire): void {
                ServiceExceptionHandler::run(
                    function () use ($data): array {
                        $printPacks = app(IssuancePrintPackService::class);
                        $numbers = $printPacks->parseIssuanceNumbers((string) ($data['issuance_numbers'] ?? ''));

                        if ($numbers->isEmpty()) {
                            throw ValidationException::withMessages([
                                'issuance_numbers' => 'Enter at least one copy number.',
                            ]);
                        }

                        $issuances = DocumentIssuanceResource::getEloquentQuery()
                            ->where(function (Builder $query) use ($numbers): void {
                                foreach ($numbers as $number) {
                                    $query->orWhereRaw('upper(issuance_number) = ?', [$number]);
                                }
                            })
                            ->get();

                        $found = $issuances
                            ->map(fn (DocumentIssuance $issuance): string => strtoupper((string) $issuance->issuance_number))
                            ->unique()
                            ->values();
                        $missing = $numbers->diff($found)->values();

                        if ($missing->isNotEmpty()) {
                            $preview = $missing->take(15)->implode(', ');
                            $extra = $missing->count() > 15 ? ' and '.($missing->count() - 15).' more' : '';

                            throw ValidationException::withMessages([
                                'issuance_numbers' => 'No accessible copy was found for: '.$preview.$extra.'.',
                            ]);
                        }

                        return $printPacks->requestForIssuances($issuances, Auth::user());
                    },
                    failureTitle: 'Print failed',
                    afterSuccess: function (array $result) use ($livewire): void {
                        self::openPrint($result, $livewire);
                    },
                );
            });
    }

    public static function printSelected(): BulkAction
    {
        return BulkAction::make('printSelectedCopies')
            ->label('Print')
            ->icon(Heroicon::Printer)
            ->visible(fn (): bool => Auth::user()?->can('PrintPdf:ControlledDocument') ?? false)
            ->requiresConfirmation()
            ->modalHeading('Print selected copies')
            ->modalDescription('Print the selected active copies. Each copy starts on a new page with its own copy number. Select copies of one document.')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, Component $livewire): void {
                ServiceExceptionHandler::run(
                    fn (): array => app(IssuancePrintPackService::class)->requestForIssuances($records, Auth::user()),
                    failureTitle: 'Print failed',
                    afterSuccess: function (array $result) use ($livewire): void {
                        self::openPrint($result, $livewire);
                    },
                );
            });
    }

    /**
     * @param  array{queued: bool, batch: DocumentIssuanceBatch}  $result
     */
    private static function openPrint(array $result, Component $livewire): void
    {
        $batch = $result['batch'];
        $count = $batch->copy_count;
        $range = $batch->first_issuance_number === $batch->last_issuance_number
            ? (string) $batch->first_issuance_number
            : $batch->first_issuance_number.'–'.$batch->last_issuance_number;

        Notification::make()
            ->title('Ready to print')
            ->body($count === 1
                ? "Copy {$range} is opening in Print."
                : "{$count} copies ({$range}) are opening in Print.")
            ->success()
            ->send();

        DirectPrint::open($livewire, DirectPrint::batchUrl($batch));
    }
}
