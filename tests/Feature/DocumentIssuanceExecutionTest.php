<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentExecutionService;
use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Exceptions\WorkflowException;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\DocumentSectionRelationManager;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        'configuration' => ['response_options' => 'Pass, Fail, N/A'],
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

it('rejects a writable master whose structured section has no blank field definitions', function (): void {
    $document = executionMasterDocument(DocumentType::FORM, DocumentStatus::DRAFT);
    ControlledDocumentSection::factory()->create([
        'document_id' => $document,
        'section_type' => ControlledDocumentSection::TYPE_TABLE,
    ]);

    expect(fn () => app(ApprovalSubmissionLifecycle::class)->assertSubmittable($document))
        ->toThrow(WorkflowException::class, "The '{$document->sections()->firstOrFail()->title}' section needs at least one issued-copy field definition.");
});

it('shows the blank field definition count for writable master sections', function (): void {
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
        'configuration' => ['response_options' => 'Pass, Fail, N/A'],
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
