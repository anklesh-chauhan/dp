<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DocumentImportBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FinalizeDocumentImportBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $batchId) {}

    public function handle(): void
    {
        $batch = DocumentImportBatch::query()->findOrFail($this->batchId);
        $items = $batch->items();
        $total = $items->count();
        $successful = $items->where('status', 'completed')->count();
        $failed = $items->where('status', 'failed')->count();
        $pending = $items->whereIn('status', ['pending', 'processing'])->count();

        if ($pending > 0) {
            self::dispatch($batch->getKey())->delay(now()->addSeconds(5));

            return;
        }

        $batch->update([
            'status' => $failed > 0 && $successful > 0 ? 'completed_with_errors' : ($failed > 0 ? 'failed' : 'completed'),
            'total_items' => $total,
            'successful_items' => $successful,
            'failed_items' => $failed,
            'completed_at' => now(),
        ]);
    }
}
