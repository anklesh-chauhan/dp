<?php

declare(strict_types=1);

use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Domain\DMS\Services\ControlledDocumentPdfService;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\ControlledDocument;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\ReportTemplate;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    $this->documentTemplate = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->id,
    ]);
    $this->documentTemplateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $this->documentTemplate->id,
    ]);
});

it('stores and reuses an integrity protected controlled document pdf', function (): void {
    Storage::fake('local');

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('render')->once()->andReturn('%PDF-1.4 regulated-document');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = ControlledDocument::factory()->create([
        'template_id' => $this->documentTemplate->id,
        'template_version_id' => $this->documentTemplateVersion->id,
    ]);
    $template = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
    ]);
    $user = User::factory()->create();
    $service = app(ControlledDocumentPdfService::class);

    $first = $service->getOrGenerate($document, $template, null, [], $user);
    $second = $service->getOrGenerate($document, $template, null, [], $user);

    expect($second->is($first))->toBeTrue()
        ->and($first->sha256)->toBe(hash('sha256', '%PDF-1.4 regulated-document'))
        ->and($first->size_bytes)->toBe(strlen('%PDF-1.4 regulated-document'));
    Storage::disk('local')->assertExists($first->path);
});

it('rejects a stored controlled document pdf whose contents were changed', function (): void {
    Storage::fake('local');

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('render')->once()->andReturn('%PDF-1.4 original');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = ControlledDocument::factory()->create([
        'template_id' => $this->documentTemplate->id,
        'template_version_id' => $this->documentTemplateVersion->id,
    ]);
    $template = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
    ]);
    $service = app(ControlledDocumentPdfService::class);
    $artifact = $service->getOrGenerate($document, $template, null, [], User::factory()->create());

    Storage::disk('local')->put($artifact->path, '%PDF-1.4 changed');

    expect(fn () => $service->assertIntegrity($artifact))
        ->toThrow(RuntimeException::class, 'integrity verification failed');
});
