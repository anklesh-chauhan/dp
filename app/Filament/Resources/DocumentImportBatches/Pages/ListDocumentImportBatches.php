<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentImportBatches\Pages;

use App\Filament\Resources\DocumentImportBatches\DocumentImportBatchResource;
use App\Jobs\FinalizeDocumentImportBatchJob;
use App\Jobs\ProcessDocumentImportItemJob;
use App\Models\DocumentImportBatch;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDocumentImportBatches extends ListRecords
{
    protected static string $resource = DocumentImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('retryFailed')
            ->label('Retry Failed Items')
            ->icon(Heroicon::ArrowPath)
            ->requiresConfirmation()
            ->visible(fn (): bool => DocumentImportBatch::query()->where('status', 'failed')->orWhere('status', 'completed_with_errors')->exists())
            ->action(function (): void {
                DocumentImportBatch::query()
                    ->whereIn('status', ['failed', 'completed_with_errors'])
                    ->with('items')
                    ->get()
                    ->each(function (DocumentImportBatch $batch): void {
                        $batch->items()->where('status', 'failed')->update(['status' => 'pending', 'error_message' => null]);
                        $batch->items()->where('status', 'pending')->pluck('id')->each(fn (int $id): mixed => ProcessDocumentImportItemJob::dispatch($id));
                        $batch->update(['status' => 'processing', 'completed_at' => null]);
                        FinalizeDocumentImportBatchJob::dispatch($batch->getKey());
                    });
            })];
    }
}
