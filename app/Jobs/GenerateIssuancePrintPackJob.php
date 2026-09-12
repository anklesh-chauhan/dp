<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\DMS\Services\IssuancePrintPackService;
use App\Models\DocumentIssuanceBatch;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateIssuancePrintPackJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [5, 15, 30];

    public int $uniqueFor = 600;

    public function __construct(
        public int $batchId,
        public int $userId,
    ) {}

    public function uniqueId(): string
    {
        return 'issuance-print-pack-'.$this->batchId;
    }

    public function handle(IssuancePrintPackService $printPacks): void
    {
        $batch = DocumentIssuanceBatch::query()->findOrFail($this->batchId);
        $user = User::query()->findOrFail($this->userId);

        $batch = $printPacks->generate($batch, $user);

        Notification::make()
            ->title('Ready to print')
            ->body('Paper copies '.$batch->first_issuance_number.'–'.$batch->last_issuance_number.' are ready.')
            ->success()
            ->actions([
                Action::make('printNow')
                    ->label('Print')
                    ->url(route('issuance-batches.print', $batch))
                    ->openUrlInNewTab(),
            ])
            ->sendToDatabase($user);
    }

    public function failed(?Throwable $exception): void
    {
        $batch = DocumentIssuanceBatch::query()->find($this->batchId);

        if ($batch instanceof DocumentIssuanceBatch) {
            app(IssuancePrintPackService::class)->markFailed(
                $batch,
                $exception?->getMessage() ?? 'The print pack could not be generated.',
            );
        }

        $user = User::query()->find($this->userId);

        if ($user instanceof User) {
            Notification::make()
                ->title('Print failed')
                ->body($exception?->getMessage() ?? 'The copies could not be prepared for print.')
                ->danger()
                ->sendToDatabase($user);
        }
    }
}
