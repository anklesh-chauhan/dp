<?php

declare(strict_types=1);

use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Domain\DMS\Services\GotenbergControlledDocumentPdfRenderer;
use App\Domain\DMS\Services\IssuancePrintPackService;
use App\Filament\Resources\LogDocuments\Pages\ListLogDocuments;
use App\Jobs\GenerateIssuancePrintPackJob;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentExecution;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\ReportTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);
    $this->seed(LookupTableSeeder::class);
    $this->issuer = User::factory()->create();
});

function paperIssuableDocument(string $typeCode = DocumentType::FORM): ControlledDocument
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', $typeCode)->firstOrFail();
    $documentType->update([
        'is_issuable' => true,
        'requires_sop_reference' => false,
    ]);
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);

    return ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'document_number' => $typeCode.'-QA-00001',
    ]);
}

it('issues paper copies without creating electronic execution records', function (): void {
    $document = paperIssuableDocument();

    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 3,
    ]);

    expect($issuances)->toHaveCount(3)
        ->and($issuances->every(fn (DocumentIssuance $issuance): bool => $issuance->isPaper()))->toBeTrue()
        ->and($issuances->pluck('copy_number')->all())->toBe([1, 2, 3])
        ->and($issuances->pluck('issuance_batch_id')->unique()->count())->toBe(1)
        ->and(DocumentExecution::query()->count())->toBe(0)
        ->and(DocumentIssuanceBatch::query()->count())->toBe(1);
});

it('does not create a print batch for electronic controlled copies', function (): void {
    $document = paperIssuableDocument();

    app(DocumentIssuanceService::class)->issue($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
    ]);

    expect(DocumentIssuance::query()->sole()->issuance_type)->toBe(DocumentIssuance::TYPE_REFERENCE)
        ->and(DocumentIssuanceBatch::query()->count())->toBe(0);
});

it('rejects invalid paper copy counts', function (int $copyCount): void {
    $document = paperIssuableDocument();

    expect(fn () => app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => $copyCount,
    ]))->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('copy_count');
    });

    expect(DocumentIssuance::query()->count())->toBe(0);
})->with([
    'zero' => 0,
    'over the limit' => 201,
]);

it('builds a cached print pack for a small paper batch', function (): void {
    Storage::fake('local');

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')
        ->once()
        ->withArgs(function ($document, $template, $issuances, $organization, $printedBy): bool {
            return $printedBy instanceof User
                && $printedBy->is($this->issuer)
                && $issuances->count() === 3;
        })
        ->andReturn('%PDF-1.4 FORM-QA-00001-C01 FORM-QA-00001-C02 FORM-QA-00001-C03');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 3,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);
    $result = app(IssuancePrintPackService::class)->request($batch, $this->issuer);

    expect($result['queued'])->toBeFalse()
        ->and($result['batch']->isPackReady())->toBeTrue()
        ->and($result['batch']->pack_sha256)->toBe(hash('sha256', '%PDF-1.4 FORM-QA-00001-C01 FORM-QA-00001-C02 FORM-QA-00001-C03'));
    Storage::disk('local')->assertExists($result['batch']->pack_path);
});

it('queues print pack generation for large paper batches', function (): void {
    Queue::fake();

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 6,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);
    $result = app(IssuancePrintPackService::class)->request($batch, $this->issuer);

    expect($result['queued'])->toBeTrue()
        ->and($result['batch']->isPackPending())->toBeTrue();

    Queue::assertPushed(GenerateIssuancePrintPackJob::class, fn (GenerateIssuancePrintPackJob $job): bool => $job->batchId === $batch->id);
});

it('renders blank write-in lines for paper form sections', function (): void {
    $section = new ControlledDocumentSection([
        'title' => 'Data entry',
        'section_type' => ControlledDocumentSection::TYPE_CHECKLIST,
        'configuration' => [],
    ]);
    $section->setRelation('executionTables', collect());
    $section->setRelation('items', collect([
        (object) ['label' => 'Room cleaned', 'unit' => null],
    ]));

    $html = view('controlled-documents.partials.paper-fill-fields', ['section' => $section])->render();

    expect($html)->toContain('Room cleaned')
        ->and($html)->toContain('________________');
});

it('renders printed_by from the requesting user when the queue has no session', function (): void {
    auth()->logout();

    $html = view('reports.partials.print-zone', [
        'items' => [
            ['token' => 'printed_by', 'label' => 'Printed By', 'show_label' => true],
        ],
        'preview' => false,
        'printedBy' => User::factory()->make(['name' => 'Queue Operator']),
    ])->render();

    expect($html)->toContain('Printed By: Queue Operator');
});

it('renders printed_by without an authenticated user', function (): void {
    auth()->logout();

    $html = view('reports.partials.print-zone', [
        'items' => [
            ['token' => 'printed_by', 'label' => 'Printed By', 'show_label' => true],
        ],
        'preview' => false,
    ])->render();

    expect($html)->toContain('Printed By: -');
});

it('merges one pdf per issued copy so each copy keeps its own issuance header', function (): void {
    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 3,
    ]);

    Http::fake(function ($request) use ($issuances) {
        expect($request->url())->toContain('/forms/pdfengines/merge');

        return Http::response('%PDF-1.4 '.$issuances->pluck('issuance_number')->implode(' '), 200);
    });

    $renderer = Mockery::mock(GotenbergControlledDocumentPdfRenderer::class)->makePartial();
    $renderer->shouldReceive('render')
        ->times(3)
        ->andReturnUsing(fn ($document, $template, DocumentIssuance $issuance): string => '%PDF-1.4 '.$issuance->issuance_number);

    $contents = $renderer->renderPack(
        $document,
        ReportTemplate::factory()->make(),
        $issuances,
        [],
        $this->issuer,
    );

    expect($contents)->toBe('%PDF-1.4 '.$issuances->pluck('issuance_number')->implode(' '));
    Http::assertSentCount(1);
});

it('does not merge a print pack that contains only one copy', function (): void {
    Http::fake();

    $document = paperIssuableDocument();
    $issuance = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 1,
    ])->first();

    $renderer = Mockery::mock(GotenbergControlledDocumentPdfRenderer::class)->makePartial();
    $renderer->shouldReceive('render')->once()->andReturn('%PDF-1.4 '.$issuance->issuance_number);

    $contents = $renderer->renderPack(
        $document,
        ReportTemplate::factory()->make(),
        collect([$issuance]),
        [],
        $this->issuer,
    );

    expect($contents)->toBe('%PDF-1.4 '.$issuance->issuance_number);
    Http::assertNothingSent();
});

it('generates a queued print pack without an authenticated session', function (): void {
    Storage::fake('local');
    auth()->logout();

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')
        ->once()
        ->withArgs(function ($document, $template, $issuances, $organization, $printedBy): bool {
            return $printedBy instanceof User && $printedBy->is($this->issuer);
        })
        ->andReturn('%PDF-1.4 queued-pack');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 1,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);

    (new GenerateIssuancePrintPackJob($batch->id, $this->issuer->id))
        ->handle(app(IssuancePrintPackService::class));

    expect($batch->fresh()->isPackReady())->toBeTrue();
});

it('issues paper copies from issuable documents without changing issue controlled copy', function (): void {
    foreach ([
        'ViewAny:LogDocument',
        'View:LogDocument',
        'Issue:DocumentIssuance',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
        'View:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    Storage::fake('local');
    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 paper-pack');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $this->issuer->givePermissionTo([
        'ViewAny:LogDocument',
        'View:LogDocument',
        'Issue:DocumentIssuance',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
        'View:ControlledDocument',
    ]);
    $this->actingAs($this->issuer);

    $document = paperIssuableDocument(DocumentType::FORM);
    $recipient = User::factory()->create();

    Livewire::test(ListLogDocuments::class)
        ->assertCanSeeTableRecords([$document])
        ->assertActionVisible(TestAction::make('issueControlledCopy')->table($document))
        ->assertActionVisible(TestAction::make('issuePaperCopies')->table($document))
        ->callAction(TestAction::make('issuePaperCopies')->table($document), [
            'issued_to_user_id' => $recipient->id,
            'copy_count' => 2,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(DocumentIssuance::query()->where('document_id', $document->id)->where('issuance_type', DocumentIssuance::TYPE_PAPER)->count())->toBe(2)
        ->and(DocumentExecution::query()->count())->toBe(0)
        ->and(DocumentIssuanceBatch::query()->where('document_id', $document->id)->sole()->isPackReady())->toBeTrue();
});

it('keeps paper copies off the issue controlled copy form', function (): void {
    foreach (['ViewAny:LogDocument', 'View:LogDocument', 'Issue:DocumentIssuance'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->issuer->givePermissionTo(['ViewAny:LogDocument', 'View:LogDocument', 'Issue:DocumentIssuance']);
    $this->actingAs($this->issuer);

    $document = paperIssuableDocument(DocumentType::FORM);

    Livewire::test(ListLogDocuments::class)
        ->mountAction(TestAction::make('issueControlledCopy')->table($document))
        ->assertFormFieldExists('issuance_type')
        ->assertFormFieldExists('copy_count');

    Livewire::test(ListLogDocuments::class)
        ->mountAction(TestAction::make('issuePaperCopies')->table($document))
        ->assertFormFieldDoesNotExist('issuance_type')
        ->assertFormFieldExists('copy_count');
});

it('downloads a ready print pack for an authorized user', function (): void {
    Storage::fake('local');
    grantPaperPrintPermissions($this->issuer);

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 paper-pack');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 2,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);
    app(IssuancePrintPackService::class)->request($batch, $this->issuer);

    $response = $this->actingAs($this->issuer)
        ->get(route('issuance-batches.print-pack', $batch));

    $response
        ->assertSuccessful()
        ->assertStreamed()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('opens a word-style print preview for a paper batch', function (): void {
    Storage::fake('local');
    grantPaperPrintPermissions($this->issuer);

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 paper-pack');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 2,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);

    $this->actingAs($this->issuer)
        ->get(route('issuance-batches.print', $batch))
        ->assertOk()
        ->assertSee('print-mode', false)
        ->assertSee('data-auto-print="1"', false)
        ->assertSee('data-action="print"', false)
        ->assertSee(route('issuance-batches.print-pack', $batch), false);
});

it('reports print status for a queued paper batch', function (): void {
    Queue::fake();
    grantPaperPrintPermissions($this->issuer);

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 6,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);
    app(IssuancePrintPackService::class)->request($batch, $this->issuer);

    $this->actingAs($this->issuer)
        ->get(route('issuance-batches.print', $batch->fresh()))
        ->assertOk()
        ->assertSee('data-poll-url', false)
        ->assertSee(route('issuance-batches.print-status', $batch), false);

    $this->actingAs($this->issuer)
        ->getJson(route('issuance-batches.print-status', $batch->fresh()))
        ->assertOk()
        ->assertJson([
            'status' => DocumentIssuanceBatch::PACK_PENDING,
            'ready' => false,
        ]);
});

it('forbids print pack download without issuance access', function (): void {
    Storage::fake('local');
    grantPaperPrintPermissions($this->issuer);

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 paper-pack');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 1,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);
    app(IssuancePrintPackService::class)->request($batch, $this->issuer);

    $stranger = User::factory()->create();
    grantPaperPrintPermissions($stranger);

    $this->actingAs($stranger)
        ->get(route('issuance-batches.print-pack', $batch->fresh()))
        ->assertForbidden();
});

it('returns locked when the print pack is still being prepared', function (): void {
    Queue::fake();
    grantPaperPrintPermissions($this->issuer);

    $document = paperIssuableDocument();
    $issuances = app(DocumentIssuanceService::class)->issueCopies($document, $this->issuer, [
        'issued_to_user_id' => $this->issuer->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'copy_count' => 6,
    ]);

    $batch = DocumentIssuanceBatch::query()->findOrFail($issuances->first()->issuance_batch_id);
    app(IssuancePrintPackService::class)->request($batch, $this->issuer);

    $this->actingAs($this->issuer)
        ->get(route('issuance-batches.print-pack', $batch->fresh()))
        ->assertStatus(423);
});

function grantPaperPrintPermissions(User $user): void
{
    $permissions = [
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user->givePermissionTo($permissions);
}
