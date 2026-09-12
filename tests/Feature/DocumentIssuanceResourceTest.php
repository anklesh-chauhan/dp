<?php

declare(strict_types=1);

use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Domain\DMS\Services\IssuancePrintPackService;
use App\Filament\Resources\DocumentIssuances\Pages\ListDocumentIssuances;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\IssuanceStatus;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);
    $this->seed(LookupTableSeeder::class);

    foreach ([
        'ViewAny:DocumentIssuance',
        'View:DocumentIssuance',
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'ViewAny:DocumentIssuance',
        'View:DocumentIssuance',
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
    ]);
    $this->actingAs($this->user);
});

function issuanceRegisterDocument(): ControlledDocument
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::FORM)->firstOrFail();
    $documentType->update(['is_issuable' => true, 'requires_sop_reference' => false]);
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create(['document_template_id' => $template]);

    return ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'document_number' => 'FORM-QA-REG-'.fake()->unique()->numerify('#####'),
    ]);
}

it('groups issuance register row actions behind an overflow menu', function (): void {
    $document = issuanceRegisterDocument();
    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_EXECUTION,
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->assertCanSeeTableRecords([$issuance])
        ->assertActionExists(TestAction::make('viewDocument')->table($issuance))
        ->assertActionExists(TestAction::make('printCopy')->table($issuance));
});

it('filters the issuance register with copy-type tabs', function (): void {
    $document = issuanceRegisterDocument();
    $reference = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C01',
        'copy_number' => 1,
    ]);
    $execution = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_EXECUTION,
        'issuance_number' => $document->document_number.'-C02',
        'copy_number' => 2,
    ]);
    $paper = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_PAPER,
        'issuance_number' => $document->document_number.'-C03',
        'copy_number' => 3,
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->assertSee('All copies')
        ->assertSee('Reference copy')
        ->assertSee('Writable execution record')
        ->assertSee('Paper copy')
        ->assertCanSeeTableRecords([$reference, $execution, $paper])
        ->set('activeTab', 'reference')
        ->assertCanSeeTableRecords([$reference])
        ->assertCanNotSeeTableRecords([$execution, $paper])
        ->set('activeTab', 'execution')
        ->assertCanSeeTableRecords([$execution])
        ->assertCanNotSeeTableRecords([$reference, $paper])
        ->set('activeTab', 'paper')
        ->assertCanSeeTableRecords([$paper])
        ->assertCanNotSeeTableRecords([$reference, $execution]);
});

it('prints selected copies of one document as a combined pack', function (): void {
    Storage::fake('local');
    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 selected-copies');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = issuanceRegisterDocument();
    $first = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C01',
        'copy_number' => 1,
    ]);
    $second = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C02',
        'copy_number' => 2,
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->selectTableRecords([$first, $second])
        ->callAction(TestAction::make('printSelectedCopies')->table()->bulk())
        ->assertNotified();

    $batch = DocumentIssuanceBatch::query()->whereJsonContains('issuance_ids', $first->id)->sole();

    expect($batch->isPackReady())->toBeTrue()
        ->and($batch->copy_count)->toBe(2)
        ->and($batch->issuance_ids)->toEqualCanonicalizing([$first->id, $second->id]);
    Storage::disk('local')->assertExists($batch->pack_path);
});

it('prints copies looked up by issuance number', function (): void {
    Storage::fake('local');
    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 numbered-copies');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = issuanceRegisterDocument();
    $first = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C11',
        'copy_number' => 11,
    ]);
    $second = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C12',
        'copy_number' => 12,
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->callAction(TestAction::make('printByIssuanceNumber')->table(), [
            'issuance_numbers' => strtolower($first->issuance_number)."\n".$second->issuance_number,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(app(IssuancePrintPackService::class)->parseIssuanceNumbers("a-c01\nB-C02")->all())
        ->toBe(['A-C01', 'B-C02'])
        ->and(app(IssuancePrintPackService::class)->parseIssuanceNumbers('SOP-QA-00001-C01,SOP-QA-00001-C02,SOP-QA-00001-C03')->all())
        ->toBe(['SOP-QA-00001-C01', 'SOP-QA-00001-C02', 'SOP-QA-00001-C03'])
        ->and(app(IssuancePrintPackService::class)->parseIssuanceNumbers('SOP-QA-00001-C01-SOP-QA-00001-C100')->all())
        ->toHaveCount(100)
        ->and(app(IssuancePrintPackService::class)->parseIssuanceNumbers('SOP-QA-00001-C01-SOP-QA-00001-C100')->first())
        ->toBe('SOP-QA-00001-C01')
        ->and(app(IssuancePrintPackService::class)->parseIssuanceNumbers('SOP-QA-00001-C01-SOP-QA-00001-C100')->last())
        ->toBe('SOP-QA-00001-C100');

    $batch = DocumentIssuanceBatch::query()->whereJsonContains('issuance_ids', $first->id)->sole();

    expect($batch->isPackReady())->toBeTrue()
        ->and($batch->issuance_ids)->toEqualCanonicalizing([$first->id, $second->id]);
});

it('prints an inclusive issuance-number range as one pack', function (): void {
    Storage::fake('local');
    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 ranged-copies');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = issuanceRegisterDocument();
    $copies = collect([1, 2, 3])->map(fn (int $copyNumber): DocumentIssuance => DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => sprintf('%s-C%02d', $document->document_number, $copyNumber),
        'copy_number' => $copyNumber,
    ]));

    $start = $copies->first()->issuance_number;
    $end = $copies->last()->issuance_number;

    Livewire::test(ListDocumentIssuances::class)
        ->callAction(TestAction::make('printByIssuanceNumber')->table(), [
            'issuance_numbers' => $start.'-'.$end,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $batch = DocumentIssuanceBatch::query()->whereJsonContains('issuance_ids', $copies->first()->id)->sole();

    expect($batch->isPackReady())->toBeTrue()
        ->and($batch->copy_count)->toBe(3)
        ->and($batch->issuance_ids)->toEqualCanonicalizing($copies->pluck('id')->all());
});

it('prints a comma-separated issuance-number list as one pack', function (): void {
    Storage::fake('local');
    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('renderPack')->once()->andReturn('%PDF-1.4 listed-copies');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $document = issuanceRegisterDocument();
    $first = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C01',
        'copy_number' => 1,
    ]);
    $second = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C02',
        'copy_number' => 2,
    ]);
    $third = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
        'issuance_number' => $document->document_number.'-C03',
        'copy_number' => 3,
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->callAction(TestAction::make('printByIssuanceNumber')->table(), [
            'issuance_numbers' => implode(',', [$first->issuance_number, $second->issuance_number, $third->issuance_number]),
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $batch = DocumentIssuanceBatch::query()->whereJsonContains('issuance_ids', $first->id)->sole();

    expect($batch->issuance_ids)->toEqualCanonicalizing([$first->id, $second->id, $third->id]);
});

it('rejects issuance ranges that mix document numbers or exceed 200 copies', function (): void {
    $printPacks = app(IssuancePrintPackService::class);

    expect(fn () => $printPacks->parseIssuanceNumbers('SOP-QA-00001-C01-SOP-QA-00002-C10'))
        ->toThrow(function (ValidationException $exception): void {
            expect($exception->errors())->toHaveKey('issuance_numbers');
        });

    expect(fn () => $printPacks->parseIssuanceNumbers('SOP-QA-00001-C01-SOP-QA-00001-C202'))
        ->toThrow(function (ValidationException $exception): void {
            expect($exception->errors())->toHaveKey('issuance_numbers');
        });
});

it('rejects a print pack that mixes master documents', function (): void {
    $firstDocument = issuanceRegisterDocument();
    $secondDocument = issuanceRegisterDocument();

    $first = DocumentIssuance::factory()->create([
        'document_id' => $firstDocument,
        'issued_to_user_id' => $this->user->id,
        'issuance_number' => $firstDocument->document_number.'-C01',
        'copy_number' => 1,
    ]);
    $second = DocumentIssuance::factory()->create([
        'document_id' => $secondDocument,
        'issued_to_user_id' => $this->user->id,
        'issuance_number' => $secondDocument->document_number.'-C01',
        'copy_number' => 1,
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->selectTableRecords([$first, $second])
        ->callAction(TestAction::make('printSelectedCopies')->table()->bulk())
        ->assertNotified();

    expect(DocumentIssuanceBatch::query()->whereNotNull('issuance_ids')->count())->toBe(0);
});

it('rejects recalled copies from a register print pack', function (): void {
    $document = issuanceRegisterDocument();
    $active = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_number' => $document->document_number.'-C21',
        'copy_number' => 21,
    ]);
    $recalled = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_number' => $document->document_number.'-C22',
        'copy_number' => 22,
        'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::RECALLED),
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->selectTableRecords([$active, $recalled])
        ->callAction(TestAction::make('printSelectedCopies')->table()->bulk())
        ->assertNotified();

    expect(DocumentIssuanceBatch::query()->whereNotNull('issuance_ids')->count())->toBe(0);
});
