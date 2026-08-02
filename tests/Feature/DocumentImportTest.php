<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentImportService;
use App\Jobs\ProcessDocumentImportItemJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('imports supported files and preserves their original artifacts', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('imports/uploads/sop-001.pdf', '%PDF-1.4 original');
    Queue::fake();

    $batch = app(DocumentImportService::class)->importFiles(
        ['imports/uploads/sop-001.pdf'],
        User::factory()->create(),
        'Customer migration',
    );

    Queue::assertPushed(ProcessDocumentImportItemJob::class);
    $item = $batch->items()->firstOrFail();
    (new ProcessDocumentImportItemJob($item->getKey()))->handle();

    $artifact = $item->refresh()->originalArtifact;
    expect($artifact->original_name)->toBe('sop-001.pdf')
        ->and($artifact->sha256)->toBe(hash('sha256', '%PDF-1.4 original'));
    Storage::disk('local')->assertExists($artifact->path);
});

it('records unsupported files without stopping the import batch', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('imports/uploads/notes.txt', 'not a controlled document');

    $batch = app(DocumentImportService::class)->importFiles(
        ['imports/uploads/notes.txt'],
        User::factory()->create(),
    );

    expect($batch)
        ->total_items->toBe(1)
        ->and($batch->items()->firstOrFail()->status)
        ->toBe('failed')
        ->and($batch->items()->firstOrFail()->error_message)
        ->toContain('Only PDF, DOC, and DOCX files are supported');
});
