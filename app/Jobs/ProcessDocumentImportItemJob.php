<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DocumentImportItem;
use App\Models\DocumentOriginalArtifact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDocumentImportItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $itemId) {}

    public function handle(): void
    {
        $item = DocumentImportItem::query()->findOrFail($this->itemId);
        if ($item->status === 'completed') {
            return;
        }

        $item->update(['status' => 'processing', 'error_message' => null]);
        $disk = Storage::disk('local');

        try {
            if (! $item->source_path || ! $disk->exists($item->source_path)) {
                throw new \RuntimeException('The staged source file is no longer available. Please re-upload this file and retry the import.');
            }

            $artifactPath = 'document-originals/'.$item->batch->batch_uuid.'/'.$item->original_name;
            $disk->put($artifactPath, $disk->get($item->source_path));
            DocumentOriginalArtifact::query()->firstOrCreate([
                'import_item_id' => $item->getKey(),
            ], [
                'disk' => 'local',
                'path' => $artifactPath,
                'original_name' => $item->original_name,
                'uploaded_by' => $item->created_by,
            ]);
            $artifact = $item->fresh()->originalArtifact;
            if ($artifact !== null && strtolower((string) $artifact->mime_type) !== 'application/pdf') {
                GenerateDocumentOriginalPreviewJob::dispatch($artifact->getKey());
            }
            $item->update(['status' => 'completed']);
        } catch (Throwable $exception) {
            $item->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
