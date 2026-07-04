<?php

declare(strict_types=1);

use App\Actions\Sop\CreateDocumentFromTemplateAction;
use App\Actions\Sop\IssueDocumentAction;
use App\Data\SopDocumentData;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\IssuanceStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\VariableDataType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function createEffectiveSop(Department $department): SopDocument
{
    $sopType = DocumentType::factory()->create([
        'name' => 'Standard Operating Procedure',
        'code' => DocumentType::SOP,
    ]);

    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => $sopType->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);

    $version = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
    ]);

    return SopDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $version->id,
        'department_id' => $department->id,
        'document_type_id' => $sopType->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'document_number' => 'SOP-'.$department->code.'-00001',
    ]);
}

function createLogTemplate(Department $department): SopTemplate
{
    $logType = DocumentType::factory()->create([
        'name' => 'Log Document',
        'code' => DocumentType::LOG,
        'requires_sop_reference' => true,
        'is_issuable' => true,
    ]);

    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => $logType->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);

    $version = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
    ]);

    $version->sections()->create([
        'title' => 'Execution Log',
        'section_order' => 1,
        'section_type' => 'rich_text',
        'content' => '<p>Execution per {{document_number}} referencing approved SOP.</p>',
        'is_required' => true,
    ]);

    $version->variables()->createMany([
        ['name' => 'department', 'label' => 'Department', 'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DEPARTMENT), 'required' => true],
        ['name' => 'document_number', 'label' => 'Document Number', 'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT), 'required' => true],
        ['name' => 'referenced_sop', 'label' => 'Referenced SOP', 'variable_data_type_id' => VariableDataType::idFor(VariableDataType::SOP_REFERENCE), 'required' => true],
    ]);

    return $template->load('publishedVersion');
}

it('requires an effective sop reference when creating log documents', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $maker = User::factory()->create(['department_id' => $department->id]);
    $template = createLogTemplate($department);

    expect(fn () => app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Equipment Cleaning Log',
        ownerId: $maker->id,
        createdBy: $maker->id,
    )))->toThrow(ValidationException::class);
});

it('creates a log document with snapshotted sop reference', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $maker = User::factory()->create(['department_id' => $department->id]);
    $effectiveSop = createEffectiveSop($department);
    $template = createLogTemplate($department);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Equipment Cleaning Log',
        ownerId: $maker->id,
        createdBy: $maker->id,
        referencedSopDocumentId: $effectiveSop->id,
        batchNumber: 'BATCH-001',
        productName: 'Product A',
        purpose: 'Routine cleaning verification',
        documentNumber: 'LOG-QA-00001',
    ));

    expect($document->documentStatus?->is(DocumentStatus::DRAFT))->toBeTrue()
        ->and($document->referenced_sop_document_id)->toBe($effectiveSop->id)
        ->and($document->referenced_sop_number)->toBe($effectiveSop->document_number)
        ->and($document->referenced_sop_version)->toBe($effectiveSop->version)
        ->and($document->batch_number)->toBe('BATCH-001')
        ->and($document->isIssuableType())->toBeTrue()
        ->and($document->variables->pluck('value', 'variable_name')->get('referenced_sop'))->toBe($effectiveSop->document_number);
});

it('issues controlled copies only after approval to effective', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $controller = User::factory()->create(['department_id' => $department->id]);
    Permission::findOrCreate('Issue:DocumentIssuance', 'web');
    $controller->givePermissionTo('Issue:DocumentIssuance');

    $effectiveSop = createEffectiveSop($department);
    $template = createLogTemplate($department);
    $maker = User::factory()->create(['department_id' => $department->id]);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Batch Log',
        ownerId: $maker->id,
        createdBy: $maker->id,
        referencedSopDocumentId: $effectiveSop->id,
        documentNumber: 'LOG-QA-00002',
    ));

    expect(fn () => app(IssueDocumentAction::class)->execute($document, $controller))
        ->toThrow(ValidationException::class);

    $document->update(['document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE)]);

    $issuance = app(IssueDocumentAction::class)->execute($document, $controller, [
        'issued_to_location' => 'Production Line 1',
    ]);

    expect($issuance->issuanceStatus?->is(IssuanceStatus::ACTIVE))->toBeTrue()
        ->and($issuance->copy_number)->toBe(1)
        ->and($issuance->issuance_number)->toBe('LOG-QA-00002-C01')
        ->and(SopAuditLog::query()->where('document_id', $document->id)->where('action', SopAuditLog::ACTION_ISSUED)->exists())->toBeTrue();
});

it('blocks printing log documents without an active controlled copy', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $user = User::factory()->create();
    Permission::findOrCreate('View:SopDocument', 'web');
    $user->givePermissionTo('View:SopDocument');

    $effectiveSop = createEffectiveSop($department);
    $template = createLogTemplate($department);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Cleaning Log',
        ownerId: $user->id,
        createdBy: $user->id,
        referencedSopDocumentId: $effectiveSop->id,
        documentNumber: 'LOG-QA-00003',
    ));

    $document->update(['document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE)]);

    actingAs($user);

    get(route('sop-documents.print', $document))->assertForbidden();
});

it('allows printing log documents with an active controlled copy', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $user = User::factory()->create();
    Permission::findOrCreate('View:SopDocument', 'web');
    Permission::findOrCreate('Issue:DocumentIssuance', 'web');
    $user->givePermissionTo(['View:SopDocument', 'Issue:DocumentIssuance']);

    $effectiveSop = createEffectiveSop($department);
    $template = createLogTemplate($department);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Cleaning Log',
        ownerId: $user->id,
        createdBy: $user->id,
        referencedSopDocumentId: $effectiveSop->id,
        documentNumber: 'LOG-QA-00004',
    ));

    $document->update(['document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE)]);
    $issuance = app(IssueDocumentAction::class)->execute($document, $user);

    actingAs($user);

    get(route('sop-documents.print', ['sopDocument' => $document, 'issuance' => $issuance->id]))
        ->assertOk()
        ->assertSee('LOG-QA-00004')
        ->assertSee('Controlled Copy 1')
        ->assertSee($effectiveSop->document_number);
});
