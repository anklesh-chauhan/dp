<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentExecutionService;
use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Exceptions\WorkflowException;
use App\Filament\Resources\ControlledDocuments\Pages\EditControlledDocument;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\DocumentSectionRelationManager;
use App\Filament\Resources\DocumentExecutions\Pages\EditDocumentExecution;
use App\Filament\Resources\DocumentExecutions\RelationManagers\SectionsRelationManager;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionItem;
use App\Models\ControlledDocumentSectionTable;
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

it('rejects an empty table inside a writable section', function (): void {
    $document = executionMasterDocument(DocumentType::FORM, DocumentStatus::DRAFT);
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
    ]);
    ControlledDocumentSectionTable::factory()->create([
        'section_id' => $section,
        'title' => 'Container details',
    ]);

    expect(fn () => app(ApprovalSubmissionLifecycle::class)->assertSubmittable($document))
        ->toThrow(WorkflowException::class, "The 'Container details' table in '{$section->title}' needs at least one column.");
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
        ->toContain("Repeater::make('executionTables')")
        ->toContain("Section::make('Execution tables')")
        ->toContain("->addActionLabel('Add another table')")
        ->not->toContain("KeyValue::make('configuration')")
        ->and($templateSectionEditor)
        ->not->toContain("KeyValue::make('configuration')");
});

it('creates multiple execution tables from the controlled document section form', function (): void {
    Gate::before(static fn (): bool => true);

    $document = executionMasterDocument(DocumentType::FORM, DocumentStatus::DRAFT);
    $document->load('documentStatus');
    $this->actingAs($this->issuer);

    Livewire::test(DocumentSectionRelationManager::class, [
        'ownerRecord' => $document,
        'pageClass' => EditControlledDocument::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'title' => 'Manufacturing details',
            'section_order' => 1,
            'section_type' => ControlledDocumentSection::TYPE_TABLE,
            'heading_level' => 1,
            'include_in_toc' => true,
            'content' => '<p>Record manufacturing details.</p>',
            'executionTables' => [
                [
                    'title' => 'Material details',
                    'execution_layout' => 'table',
                    'row_count' => 3,
                    'items' => [
                        [
                            'label' => 'Material',
                            'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
                            'is_required' => true,
                        ],
                        [
                            'label' => 'Qty',
                            'value_type' => ControlledDocumentSectionItem::VALUE_NUMERIC,
                            'unit' => 'kg',
                            'decimal_precision' => 2,
                            'is_required' => true,
                        ],
                    ],
                ],
                [
                    'title' => 'Cleaning details',
                    'execution_layout' => 'field_value',
                    'row_count' => 1,
                    'items' => [
                        [
                            'label' => 'Cleaning date',
                            'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
                            'is_required' => true,
                        ],
                    ],
                ],
            ],
        ])
        ->assertHasNoActionErrors();

    $section = $document->sections()->with('executionTables.items')->firstOrFail();

    expect($section->executionTables)->toHaveCount(2)
        ->and($section->executionTables->pluck('title')->all())->toBe(['Material details', 'Cleaning details'])
        ->and($section->executionTables[0]->row_count)->toBe(3)
        ->and($section->executionTables[0]->items)->toHaveCount(2)
        ->and($section->executionTables[1]->execution_layout)->toBe('field_value')
        ->and($section->executionTables[1]->items)->toHaveCount(1)
        ->and($section->items()->whereNull('section_table_id')->count())->toBe(0);
});

it('presents execution entries as a compact table with understandable field labels', function (): void {
    $executionEditor = file_get_contents(app_path('Filament/Resources/DocumentExecutions/RelationManagers/SectionsRelationManager.php'));
    $executionGrid = file_get_contents(resource_path('views/filament/forms/components/execution-grid.blade.php'));
    $adminTheme = file_get_contents(resource_path('css/filament/admin/theme.css'));

    expect($executionEditor)
        ->toContain("label: filled(\$title) ? \$title : 'Execution entries'")
        ->toContain('ExecutionGrid::make($statePath)')
        ->toContain("'key' => \$this->executionFieldKey(\$field)")
        ->toContain("'value_type' => \$field->value_type")
        ->toContain("'decimal_precision' => \$field->decimal_precision")
        ->toContain("'step' => \$this->numericStep(\$field->decimal_precision)")
        ->toContain('executionTablesState')
        ->toContain('updateExecutionTables')
        ->toContain("isDirty(['response', 'comments', 'verified_by'])")
        ->toContain('->modalWidth(Width::Full)')
        ->not->toContain("TextInput::make('scheduled_at')->disabled()")
        ->not->toContain("TextInput::make('label')->disabled()")
        ->and($executionGrid)
        ->toContain("column.value_type === 'numeric'")
        ->toContain('x-bind:step="column.step"')
        ->toContain("column.value_type === 'boolean'")
        ->toContain('<option value="Pass">Pass</option>')
        ->toContain('<option value="Fail">Fail</option>')
        ->toContain('column.decimal_precision')
        ->toContain('x-text="column.unit"')
        ->toContain('overflow-x-auto')
        ->toContain('<span class="sr-only">Row number</span>')
        ->toContain('even:bg-gray-50/50')
        ->not->toContain('sticky left-0')
        ->and($adminTheme)
        ->toContain("@source '../../../../app/Filament/**/*';")
        ->toContain("@source '../../../../resources/views/filament/**/*';");
});

it('opens the structured execution section editor', function (): void {
    Gate::before(static fn (): bool => true);

    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
        'configuration' => ['execution_row_count' => 4],
    ]);

    $columns = [
        ['label' => 'Material', 'value_type' => ControlledDocumentSectionItem::VALUE_TEXT],
        ['label' => 'Qty', 'value_type' => ControlledDocumentSectionItem::VALUE_NUMERIC, 'unit' => 'kg', 'decimal_precision' => 2],
        ['label' => 'Cleaning complete', 'value_type' => ControlledDocumentSectionItem::VALUE_BOOLEAN],
    ];

    foreach ($columns as $order => $column) {
        ControlledDocumentSectionItem::query()->create([
            'section_id' => $section->id,
            'item_order' => $order + 1,
            ...$column,
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
            expect($data['execution_tables']['legacy'])->toHaveCount(4)
                ->and($data['execution_tables']['legacy'][0]['row_label'])->toBe('1')
                ->and($data['execution_tables']['legacy'][3]['row_label'])->toBe('4');

            return [];
        });

    $tables = $component->get('mountedActions.0.data.execution_tables');
    $materialItem = $executionSection->items()->where('row_number', 1)->orderBy('id')->firstOrFail();
    $tables['legacy'][0]['responses']['field_'.$materialItem->source_item_id] = 'Stainless steel vessel';

    $component
        ->setActionData(['execution_tables' => $tables])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($materialItem->fresh()->response)->toBe('Stainless steel vessel');
});

it('issues, edits, and prints multiple execution tables within one section', function (): void {
    Gate::before(static fn (): bool => true);

    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
    ]);
    $materialsTable = ControlledDocumentSectionTable::factory()->create([
        'section_id' => $section,
        'title' => 'Material details',
        'table_order' => 1,
        'execution_layout' => 'table',
        'row_count' => 2,
    ]);
    $materialsTable->items()->create([
        'item_order' => 1,
        'label' => 'Material',
        'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
        'is_required' => true,
    ]);
    $materialsTable->items()->create([
        'item_order' => 2,
        'label' => 'Qty',
        'value_type' => ControlledDocumentSectionItem::VALUE_NUMERIC,
        'unit' => 'kg',
        'decimal_precision' => 2,
        'is_required' => true,
    ]);
    $cleaningTable = ControlledDocumentSectionTable::factory()->create([
        'section_id' => $section,
        'title' => 'Cleaning details',
        'table_order' => 2,
        'execution_layout' => 'field_value',
        'row_count' => 1,
    ]);
    $cleaningTable->items()->create([
        'item_order' => 1,
        'label' => 'Cleaning date',
        'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
        'is_required' => true,
    ]);

    $execution = app(DocumentIssuanceService::class)->issue($document, $this->issuer)->execution;
    $executionSection = $execution->sections()->with('items')->firstOrFail();

    expect($executionSection->items)->toHaveCount(5)
        ->and($executionSection->items->where('source_table_id', $materialsTable->id))->toHaveCount(4)
        ->and($executionSection->items->where('source_table_id', $cleaningTable->id))->toHaveCount(1)
        ->and($executionSection->items->pluck('table_title')->unique()->values()->all())
        ->toBe(['Material details', 'Cleaning details']);

    $this->actingAs($this->issuer);

    $component = Livewire::test(SectionsRelationManager::class, [
        'ownerRecord' => $execution,
        'pageClass' => EditDocumentExecution::class,
    ]);
    $component->mountAction(TestAction::make('edit')->table($executionSection));
    $tables = $component->get('mountedActions.0.data.execution_tables');
    $materialKey = 'table_1';
    $cleaningKey = 'table_2';
    $materialItem = $executionSection->items->firstWhere('source_table_id', $materialsTable->id);
    $cleaningItem = $executionSection->items->firstWhere('source_table_id', $cleaningTable->id);

    expect($tables[$materialKey])->toHaveCount(2)
        ->and($tables[$cleaningKey])->toHaveCount(1);

    $tables[$materialKey][0]['responses']['field_'.$materialItem->source_item_id] = 'Citric acid';
    $tables[$cleaningKey][0]['responses']['field_'.$cleaningItem->source_item_id] = '2026-08-10';

    $component
        ->setActionData(['execution_tables' => $tables])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $html = view('controlled-documents.partials.execution-table', [
        'section' => $executionSection->fresh(['items.completedBy', 'items.verifiedBy']),
    ])->render();

    expect($materialItem->fresh()->response)->toBe('Citric acid')
        ->and($cleaningItem->fresh()->response)->toBe('2026-08-10')
        ->and(substr_count($html, '<table>'))->toBe(2)
        ->and($html)
        ->toContain('Material details')
        ->toContain('Cleaning details')
        ->toContain('Material')
        ->toContain('Qty')
        ->toContain('Cleaning date')
        ->toContain('Citric acid')
        ->toContain('2026-08-10');
});

it('uses execution field names as the issued table headers', function (): void {
    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
        'configuration' => ['execution_row_count' => 3],
    ]);
    $columns = [
        ['label' => 'Material', 'value_type' => ControlledDocumentSectionItem::VALUE_TEXT],
        ['label' => 'Qty', 'value_type' => ControlledDocumentSectionItem::VALUE_NUMERIC, 'unit' => 'kg', 'decimal_precision' => 2],
        ['label' => 'Cleaning complete', 'value_type' => ControlledDocumentSectionItem::VALUE_BOOLEAN],
    ];

    foreach ($columns as $order => $column) {
        ControlledDocumentSectionItem::query()->create([
            'section_id' => $section->id,
            'item_order' => $order + 1,
            ...$column,
            'is_required' => true,
        ]);
    }

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer);
    $issuedSection = $issuance->execution->sections()->with('items')->firstOrFail();
    $firstRow = $issuedSection->items->where('row_number', 1)->values();
    $executor = User::factory()->create(['name' => 'Execution Operator']);
    $verifier = User::factory()->create(['name' => 'QA Verifier']);
    $firstRow[0]->update(['response' => 'Citric acid', 'completed_by' => $executor->id, 'verified_by' => $verifier->id]);
    $firstRow[1]->update(['response' => '25.5', 'completed_by' => $executor->id, 'verified_by' => $verifier->id]);
    $firstRow[2]->update(['response' => 'Pass', 'completed_by' => $executor->id, 'verified_by' => $verifier->id]);

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
        ->toContain('(kg)')
        ->toContain('Cleaning complete')
        ->toContain('Citric acid')
        ->toContain('25.50')
        ->toContain('Pass')
        ->toContain('Completed by')
        ->toContain('Execution Operator')
        ->toContain('Verified by')
        ->toContain('QA Verifier')
        ->not->toContain('Date / Time')
        ->not->toContain('Completed / Verified');
});

it('aligns printed execution values with their headers when column orders are duplicated', function (): void {
    $document = executionMasterDocument();
    $section = ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
        'configuration' => ['execution_row_count' => 2],
    ]);

    foreach (['Material', 'Qty', 'Temp'] as $label) {
        ControlledDocumentSectionItem::query()->create([
            'section_id' => $section->id,
            'item_order' => 1,
            'label' => $label,
            'value_type' => ControlledDocumentSectionItem::VALUE_TEXT,
            'is_required' => true,
        ]);
    }

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer);
    $issuedSection = $issuance->execution->sections()->with('items')->firstOrFail();
    $rows = $issuedSection->items->groupBy('row_number');

    foreach ($rows[1] as $item) {
        $item->update(['response' => 'First '.$item->label]);
    }

    foreach ($rows[2] as $item) {
        $item->update(['response' => 'Second '.$item->label]);
    }

    $issuedSection->setRelation('items', $rows[1]->concat($rows[2]->reverse())->values());

    $html = view('controlled-documents.partials.execution-table', [
        'section' => $issuedSection,
    ])->render();

    preg_match_all('/<tr>(.*?)<\/tr>/s', $html, $renderedRows);

    expect($renderedRows[1])->toHaveCount(3)
        ->and(strip_tags($renderedRows[1][2]))
        ->toContain('Second Material')
        ->toContain('Second Qty')
        ->toContain('Second Temp')
        ->and(strpos($renderedRows[1][2], 'Second Material'))->toBeLessThan(strpos($renderedRows[1][2], 'Second Qty'))
        ->and(strpos($renderedRows[1][2], 'Second Qty'))->toBeLessThan(strpos($renderedRows[1][2], 'Second Temp'));
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
