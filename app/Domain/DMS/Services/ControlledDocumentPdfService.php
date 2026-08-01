<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentPdf;
use App\Models\DocumentIssuance;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ControlledDocumentPdfService
{
    public function __construct(private readonly ControlledDocumentPdfRenderer $renderer) {}

    /** @param array<string, mixed> $organization */
    public function getOrGenerate(
        ControlledDocument $document,
        ReportTemplate $reportTemplate,
        ?DocumentIssuance $issuance,
        array $organization,
        User $generatedBy,
    ): ControlledDocumentPdf {
        $artifactKey = hash('sha256', implode('|', [
            $document->id,
            $document->version,
            $reportTemplate->id ?? $reportTemplate->layout_key,
            $issuance?->id ?? 'master',
            config('laravel-pdf.controlled_document_layout_version'),
        ]));
        $existing = ControlledDocumentPdf::query()
            ->where('artifact_key', $artifactKey)
            ->first();

        if ($existing !== null) {
            $this->assertIntegrity($existing);

            return $existing;
        }

        $contents = $this->renderer->render($document, $reportTemplate, $issuance, $organization);

        if (! str_starts_with($contents, '%PDF-')) {
            throw new RuntimeException('The document renderer did not return a valid PDF.');
        }

        $sha256 = hash('sha256', $contents);
        $filename = sprintf('%s-v%s%s.pdf', $document->document_number, $document->version, $issuance ? '-copy-'.$issuance->copy_number : '');
        $path = sprintf('controlled-document-pdfs/%s/v%s/%s.pdf', $document->document_series_id, $document->version, $sha256);
        $disk = 'local';

        if (! Storage::disk($disk)->put($path, $contents)) {
            throw new RuntimeException('The generated PDF could not be stored.');
        }

        $artifact = ControlledDocumentPdf::query()->firstOrCreate([
            'artifact_key' => $artifactKey,
        ], [
            'controlled_document_id' => $document->id,
            'report_template_id' => $reportTemplate->id,
            'document_issuance_id' => $issuance?->id,
            'document_version' => $document->version,
            'template_layout_key' => $reportTemplate->layout_key,
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
            'mime_type' => 'application/pdf',
            'size_bytes' => strlen($contents),
            'sha256' => $sha256,
            'renderer' => 'gotenberg',
            'renderer_version' => config('laravel-pdf.gotenberg.version'),
            'generated_by' => $generatedBy->id,
            'generated_at' => now(),
            'metadata' => [
                'document_number' => $document->document_number,
                'document_title' => $document->title,
                'issuance_number' => $issuance?->issuance_number,
                'watermark_code' => $issuance?->watermark_code,
                'layout_version' => config('laravel-pdf.controlled_document_layout_version'),
            ],
        ]);

        $this->assertIntegrity($artifact);

        return $artifact;
    }

    public function assertIntegrity(ControlledDocumentPdf $artifact): void
    {
        $contents = Storage::disk($artifact->disk)->get($artifact->path);

        if (! hash_equals($artifact->sha256, hash('sha256', $contents))) {
            throw new RuntimeException('Stored controlled-document PDF integrity verification failed.');
        }
    }
}
