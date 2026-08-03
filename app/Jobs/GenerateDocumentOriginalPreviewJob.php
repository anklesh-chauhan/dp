<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DocumentOriginalArtifact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateDocumentOriginalPreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $artifactId) {}

    public function handle(): void
    {
        $artifact = DocumentOriginalArtifact::query()->findOrFail($this->artifactId);
        if (strtolower((string) $artifact->mime_type) === 'application/pdf' || $artifact->preview_path !== null) {
            return;
        }

        $disk = Storage::disk($artifact->disk);
        try {
            $response = Http::timeout((int) config('services.gotenberg.timeout', 120))
                ->attach('files', $disk->get($artifact->path), $artifact->original_name)
                ->post(rtrim((string) config('services.gotenberg.url'), '/').'/forms/libreoffice/convert');
            $response->throw();
            $contents = $response->body();
            $previewPath = 'document-previews/'.Str::uuid().'.pdf';
            $disk->put($previewPath, $contents);
            DocumentOriginalArtifact::query()->whereKey($artifact->getKey())->update([
                'preview_path' => $previewPath,
                'preview_mime_type' => 'application/pdf',
                'preview_sha256' => hash('sha256', $contents),
                'preview_generated_at' => now(),
                'preview_error' => null,
            ]);
        } catch (Throwable $exception) {
            DocumentOriginalArtifact::query()->whereKey($artifact->getKey())->update(['preview_error' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
