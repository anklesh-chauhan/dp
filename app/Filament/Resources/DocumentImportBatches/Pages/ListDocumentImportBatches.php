<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentImportBatches\Pages;

use App\Domain\DMS\Services\DocumentImportService;
use App\Filament\Resources\DocumentImportBatches\DocumentImportBatchResource;
use App\Jobs\FinalizeDocumentImportBatchJob;
use App\Jobs\ProcessDocumentImportItemJob;
use App\Models\DocumentImportBatch;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDocumentImportBatches extends ListRecords
{
    protected static string $resource = DocumentImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryFailed')
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
            }),

            Action::make('importDocuments')
                ->label('Import Documents')
                ->icon(Heroicon::ArrowUpTray)
                ->schema([
                    TextInput::make('name')->label('Import batch name')->maxLength(255),
                    FileUpload::make('files')
                        ->label('PDF or Word files')
                        ->disk('local')
                        ->directory('imports/uploads')
                        ->visibility('private')
                        ->multiple()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->maxSize(50_000)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $paths = array_values(array_filter((array) ($data['files'] ?? []), 'is_string'));
                    app(DocumentImportService::class)->importFiles($paths, auth()->user(), $data['name'] ?? null);
                }),
            Action::make('importDocumentZip')
                ->label('Import ZIP Batch')
                ->icon(Heroicon::ArchiveBoxArrowDown)
                ->schema([
                    TextInput::make('name')->label('Import batch name')->maxLength(255),
                    FileUpload::make('archive')
                        ->label('ZIP archive')
                        ->disk('local')
                        ->directory('imports/uploads')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(500_000)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(DocumentImportService::class)->importZip((string) $data['archive'], auth()->user(), $data['name'] ?? null);
                }),
            ];
    }
}
