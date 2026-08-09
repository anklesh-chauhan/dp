<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentExecutionService;
use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Exceptions\WorkflowException;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\DocumentSectionRelationManager;
use App\Filament\Resources\DocumentExecutions\Pages\EditDocumentExecution;
use App\Filament\Resources\DocumentExecutions\RelationManagers\SectionsRelationManager;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionItem;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentExecution;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    $this->issuer = User::factory()->create();
});

function executionMasterDocument(
    string $documentTypeCode = DocumentType::LOG,
    string $documentStatusCode = DocumentStatus::EFFECTIVE,
): ControlledDocument {
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', $documentTypeCode)->firstOrFail();
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
        'document_status_id' => DocumentStatus::idFor($documentStatusCode),
        'document_number' => $documentTypeCode.'-QA-00001',
    ]);
}

it('validates writable master definitions without treating them as completed execution records', function (string $documentTypeCode): void {
    $document = executionMasterDocument($documentTypeCode, DocumentStatus::DRAFT);
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => $documentTypeCode === DocumentType::LOG
            ? ControlledDocumentSection::TYPE_REPEATING_LOG
            : ControlledDocumentSection::TYPE_CHECKLIST,
    ]);

    if ($section->requiresFieldDefinitions()) {
        ControlledDocumentSectionItem::query()->create([
            'section_id' => $section->id,
            'label' => 'Blank issued-copy field',
            'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
            'is_required' => true,
        ]);
    }

    app(ApprovalSubmissionLifecycle::class)->assertSubmittable($document);

    expect($document->sections()->count())->toBe(1);
})->with([
    DocumentType::FORM,
    DocumentType::LOG,
    DocumentType::CHECKLIST,
    DocumentType::BATCH_RECORD,
    DocumentType::BATCH_PACKAGING_RECORD,
]);

it('rejects a writable master whose structured section has no execution fields', function (): void {
    $document = executionMasterDocument(DocumentType::FORM, DocumentStatus::DRAFT);
    ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
    ]);

    expect(fn () => app(ApprovalSubmissionLifecycle::class)->assertSubmittable($document))
        ->toThrow(WorkflowException::class, "The '{$document->sections()->firstOrFail()->title}' section needs at least one execution field.");
});

it('shows the execution field count for writable master sections', function (): void {
    $document = executionMasterDocument(DocumentType::FORM, DocumentStatus::DRAFT);
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
    ]);

    Livewire::test(DocumentSectionRelationManager::class, [
        'ownerRecord' => $document,
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertCanSeeTableRecords([$section])
        ->assertTableColumnStateSet('field_definitions', '0', $section);
});

it('does not populate unused structured configuration defaults', function (): void {
    $document = executionMasterDocument(DocumentType::FORM, DocumentStatus::DRAFT);
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
    ]);

    expect($section->fresh()->configuration)->toBeNull();
});

it('uses execution fields instead of a generic structured configuration editor', function (): void {
    $masterSectionEditor = file_get_contents(app_path('Filament/Resources/ControlledDocuments/RelationManagers/DocumentSectionRelationManager.php'));
    $templateSectionEditor = file_get_contents(app_path('Filament/Resources/DocumentTemplates/RelationManagers/SectionRelationManager.php'));

    expect($masterSectionEditor)
        ->toContain("Repeater::make('items')")
        ->toContain("->label('Execution fields')")
        ->not->toContain("KeyValue::make('configuration')")
        ->and($templateSectionEditor)
        ->not->toContain("KeyValue::make('configuration')");
});

it('presents execution entries as a compact table with understandable field labels', function (): void {
    $executionEditor = file_get_contents(app_path('Filament/Resources/DocumentExecutions/RelationManagers/SectionsRelationManager.php'));

    expect($executionEditor)
        ->toContain("->label('Execution entries')")
        ->toContain("ExecutionGrid::make('execution_rows')")
        ->toContain("'key' => \$this->executionFieldKey(\$field)")
        ->toContain('executionRowsState')
        ->toContain('updateExecutionRows')
        ->toContain("isDirty(['response', 'comments', 'verified_by'])")
        ->toContain('->modalWidth(Width::Full)')
        ->not->toContain("TextInput::make('scheduled_at')->disabled()")
        ->not->toContain("TextInput::make('label')->disabled()");
});

it('opens the structured execution section editor', function (): void {
    Gate::before(static fn (): bool => true);

    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
        'configuration' => ['execution_row_count' => 4],
    ]);

    foreach (['Material', 'Qty', 'Cleaning date'] as $order => $header) {
        ControlledDocumentSectionItem::query()->create([
            'section_id' => $section->id,
            'item_order' => $order + 1,
            'label' => $header,
            'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
            'is_required' => true,
        ]);
    }

    $execution = app(DocumentIssuanceService::class)->issue($document, $this->issuer)->execution;
    $executionSection = $execution->sections()->firstOrFail();

    $this->actingAs($this->issuer);

    $component = Livewire::test(SectionsRelationManager::class, [
        'ownerRecord' => $execution,
        'pageClass' => EditDocumentExecution::class,
    ]);

    $component->mountAction(TestAction::make('edit')->table($executionSection))
        ->assertActionMounted(TestAction::make('edit')->table($executionSection))
        ->assertActionDataSet(function (array $data): array {
            expect($data['execution_rows'])->toHaveCount(4)
                ->and($data['execution_rows'][0]['row_label'])->toBe('1')
                ->and($data['execution_rows'][3]['row_label'])->toBe('4');

            return [];
        });

    $rows = $component->get('mountedActions.0.data.execution_rows');
    $materialItem = $executionSection->items()->where('row_number', 1)->orderBy('id')->firstOrFail();
    $rows[0]['responses']['field_'.$materialItem->source_item_id] = 'Stainless steel vessel';

    $component
        ->setActionData(['execution_rows' => $rows])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($materialItem->fresh()->response)->toBe('Stainless steel vessel');
});

it('uses execution field names as the issued table headers', function (): void {
    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
        'configuration' => ['execution_row_count' => 3],
    ]);
    foreach (['Material', 'Qty', 'Cleaning date'] as $order => $header) {
        ControlledDocumentSectionItem::query()->create([
            'section_id' => $section->id,
            'item_order' => $order + 1,
            'label' => $header,
            'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
            'is_required' => true,
        ]);
    }

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer);
    $issuedSection = $issuance->execution->sections()->with('items')->firstOrFail();
    $firstRow = $issuedSection->items->where('row_number', 1)->values();
    $firstRow[0]->update(['response' => 'Citric acid']);
    $firstRow[1]->update(['response' => '25 kg']);
    $firstRow[2]->update(['response' => '2026-08-09']);

    $html = view('controlled-documents.partials.execution-table', [
        'section' => $issuedSection->fresh(['items.completedBy', 'items.verifiedBy']),
    ])->render();

    expect($issuedSection->items)->toHaveCount(9)
        ->and($issuedSection->items->pluck('row_number')->unique()->values()->all())->toBe([1, 2, 3])
        ->and(substr_count($html, '<tr>'))->toBe(4)
        ->and(substr_count($html, 'Material'))->toBe(1)
        ->and($html)
        ->toContain('Material')
        ->toContain('Qty')
        ->toContain('Cleaning date')
        ->toContain('Citric acid')
        ->toContain('25 kg')
        ->toContain('2026-08-09')
        ->not->toContain('Date / Time')
        ->not->toContain('Completed / Verified');
});

it('renders execution fields as a vertical field and value form when configured', function (): void {
    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
        'configuration' => [
            'execution_layout' => 'field_value',
            'execution_row_count' => 1,
        ],
    ]);
    foreach (['Date', 'Lot No.', 'Amount of PBS used'] as $order => $header) {
        ControlledDocumentSectionItem::query()->create([
            'section_id' => $section->id,
            'item_order' => $order + 1,
            'label' => $header,
            'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
            'is_required' => true,
        ]);
    }

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer);
    $issuedSection = $issuance->execution->sections()->with('items')->firstOrFail();
    $issuedSection->items[0]->update(['response' => '2026-08-09']);
    $issuedSection->items[1]->update(['response' => 'LOT-001']);
    $issuedSection->items[2]->update(['response' => '40000 ml']);

    $html = view('controlled-documents.partials.execution-table', [
        'section' => $issuedSection->fresh(['items.completedBy', 'items.verifiedBy']),
    ])->render();

    expect($html)
        ->toContain('Date')
        ->toContain('2026-08-09')
        ->toContain('Lot No.')
        ->toContain('LOT-001')
        ->toContain('Amount of PBS used')
        ->toContain('40000 ml')
        ->not->toContain('Entry 1');
});

it('classifies copy behavior from the document format profile', function (string $documentTypeCode, bool $requiresExecution): void {
    $documentType = DocumentType::query()->where('code', $documentTypeCode)->firstOrFail();

    expect($documentType->requiresExecutionRecord())->toBe($requiresExecution);
})->with([
    [DocumentType::SOP, false],
    [DocumentType::POLICY, false],
    [DocumentType::MANUAL, false],
    [DocumentType::FORM, true],
    [DocumentType::LOG, true],
    [DocumentType::CHECKLIST, true],
    [DocumentType::BATCH_RECORD, true],
    [DocumentType::BATCH_PACKAGING_RECORD, true],
    ['REPORT', false],
    ['PROTOCOL', false],
    ['SPEC', false],
    ['VALIDATION', false],
    [DocumentType::ANNEXURE, false],
]);

it('snapshots blank master field definitions into each issued copy', function (): void {
    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_CHECKLIST,
    ]);
    $item = ControlledDocumentSectionItem::query()->create([
        'section_id' => $section->id,
        'item_order' => 1,
        'label' => 'Area is clean',
        'value_type' => ControlledDocumentSectionItem::VALUE_BOOLEAN,
        'is_required' => true,
    ]);

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer);
    $issuedSection = $issuance->execution->sections()->with('items')->firstOrFail();

    expect($issuance->issuance_type)->toBe(DocumentIssuance::TYPE_EXECUTION)
        ->and($issuedSection->source_section_id)->toBe($section->id)
        ->and($issuedSection->items)->toHaveCount(1)
        ->and($issuedSection->items->first()->source_item_id)->toBe($item->id)
        ->and($issuedSection->items->first()->response)->toBeNull()
        ->and($section->fresh()->items->first()->response)->toBeNull();
});

it('generates a full day of hourly entries on the issued copy only', function (): void {
    $document = executionMasterDocument();
    ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_REPEATING_LOG,
    ]);

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer, [
        'log_frequency' => 'hourly',
        'log_period_start' => '2026-08-07',
        'log_period_end' => '2026-08-07',
        'supervisor_id' => User::factory()->create()->id,
    ]);

    expect($issuance->execution->sections()->firstOrFail()->items)->toHaveCount(24)
        ->and($document->sections()->firstOrFail()->items)->toHaveCount(0);
});

it('stores completion and independent verification on issued items without changing the master', function (): void {
    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_CHECKLIST,
    ]);
    ControlledDocumentSectionItem::query()->create([
        'section_id' => $section->id,
        'label' => 'Line clearance complete',
        'value_type' => ControlledDocumentSectionItem::VALUE_BOOLEAN,
        'is_required' => true,
    ]);
    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer);
    $verifier = User::factory()->create();
    $issuedItem = $issuance->execution->sections()->firstOrFail()->items()->firstOrFail();

    $this->actingAs($this->issuer);
    $issuedItem->update(['response' => 'pass', 'verified_by' => $verifier->id]);

    expect($issuedItem->fresh()->completed_by)->toBe($this->issuer->id)
        ->and($issuedItem->fresh()->isIndependentlyVerified())->toBeTrue()
        ->and($section->items()->firstOrFail()->response)->toBeNull();
});

it('never creates a writable record for a reference issuance', function (): void {
    $document = executionMasterDocument();

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer, [
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
    ]);

    expect($issuance->isReference())->toBeTrue()
        ->and($issuance->execution)->toBeNull();
});

it('rejects an unsupported controlled-copy type', function (): void {
    $document = executionMasterDocument();

    expect(fn () => app(DocumentIssuanceService::class)->issue($document, $this->issuer, [
        'issuance_type' => 'editable_pdf',
    ]))->toThrow(ValidationException::class, 'Select either a read-only reference copy or a writable execution record.');
});

it('routes a completed log through independent supervisor review and then locks it', function (): void {
    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_REPEATING_LOG,
    ]);
    ControlledDocumentSectionItem::query()->create([
        'section_id' => $section->id,
        'label' => 'Temperature',
        'value_type' => ControlledDocumentSectionItem::VALUE_NUMERIC,
        'is_required' => true,
    ]);
    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer, [
        'supervisor_id' => User::factory()->create()->id,
    ]);
    $execution = $issuance->execution;
    $this->actingAs($this->issuer);
    $item = $execution->sections()->firstOrFail()->items()->firstOrFail();
    $item->update(['response' => '22.5']);
    $execution->sections()->firstOrFail()->update(['status' => 'completed']);

    $execution = app(DocumentExecutionService::class)->complete($execution, $this->issuer);
    $reviewer = User::factory()->create();
    $execution = app(DocumentExecutionService::class)->review($execution, $reviewer, 'Entries checked.');

    expect($execution->status)->toBe(DocumentExecution::STATUS_CLOSED)
        ->and($execution->reviewed_by)->toBe($reviewer->id);

    expect(fn () => $item->update(['response' => '23.0']))
        ->toThrow(LogicException::class);
});
