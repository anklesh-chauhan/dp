<?php

declare(strict_types=1);

use App\Actions\Sop\CreateDocumentFromTemplateAction;
use App\Actions\Sop\PublishTemplateAction;
use App\Data\SopDocumentData;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\VariableDataType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('creates an SOP document with template sections and resolved variables', function (): void {
    $department = Department::factory()->create(['name' => 'Quality Assurance', 'code' => 'QA']);
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);

    $version = SopTemplateVersion::factory()
        ->published()
        ->create([
            'sop_template_id' => $template->id,
            'version' => 1,
        ]);

    $version->sections()->create([
        'title' => 'Purpose',
        'section_order' => 1,
        'section_type' => 'rich_text',
        'content' => '<p>{{equipment}} for {{department}} on {{inspection_date}}. Shutdown: {{requires_shutdown}}.</p>',
        'is_required' => true,
    ]);

    $version->variables()->createMany([
        [
            'name' => 'equipment',
            'label' => 'Equipment',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
            'required' => true,
        ],
        [
            'name' => 'department',
            'label' => 'Department',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DEPARTMENT),
            'required' => true,
        ],
        [
            'name' => 'document_number',
            'label' => 'Document Number',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DOCUMENT_NUMBER),
            'required' => true,
        ],
        [
            'name' => 'inspection_date',
            'label' => 'Inspection Date',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DATE),
            'validation_rules' => ['date' => null],
            'required' => true,
        ],
        [
            'name' => 'requires_shutdown',
            'label' => 'Requires Shutdown',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::BOOLEAN),
            'validation_rules' => ['boolean'],
            'required' => true,
        ],
    ]);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Cleaning SOP',
        ownerId: $owner->id,
        createdBy: $creator->id,
        variables: [
            'equipment' => 'Mixer',
            'inspection_date' => '2026-06-28',
            'requires_shutdown' => false,
        ],
        documentNumber: 'SOP-QA-00001',
    ));

    expect($document)
        ->toBeInstanceOf(SopDocument::class)
        ->and($document->sections)->toHaveCount(1)
        ->and($document->sections->first()->content)->toContain('Mixer for Quality Assurance on 2026-06-28. Shutdown: No.')
        ->and($document->variables->pluck('value', 'variable_name')->all())->toMatchArray([
            'equipment' => 'Mixer',
            'department' => (string) $department->id,
            'document_number' => 'SOP-QA-00001',
            'inspection_date' => '2026-06-28',
            'requires_shutdown' => '0',
        ]);
});

it('creates an SOP document from a specific published template version', function (): void {
    $department = Department::factory()->create(['name' => 'Quality Assurance', 'code' => 'QA']);
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 2,
    ]);

    $versionOne = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
    ]);

    $versionTwo = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 2,
    ]);

    $versionOne->sections()->create([
        'title' => 'Legacy Procedure',
        'section_order' => 1,
        'section_type' => 'rich_text',
        'content' => '<p>Legacy content for {{department}}.</p>',
        'is_required' => true,
    ]);

    $versionTwo->sections()->create([
        'title' => 'Updated Procedure',
        'section_order' => 1,
        'section_type' => 'rich_text',
        'content' => '<p>Updated content for {{department}}.</p>',
        'is_required' => true,
    ]);

    foreach ([$versionOne, $versionTwo] as $version) {
        $version->variables()->createMany([
            ['name' => 'department', 'label' => 'Department', 'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DEPARTMENT), 'required' => true],
            ['name' => 'document_number', 'label' => 'Document Number', 'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT), 'required' => true],
        ]);
    }

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Versioned SOP',
        ownerId: $owner->id,
        createdBy: $creator->id,
        variables: [],
        templateVersionId: $versionOne->id,
        documentNumber: 'SOP-QA-00010',
    ));

    expect($document->template_version_id)->toBe($versionOne->id)
        ->and($document->sections->first()->content)->toContain('Legacy content');
});

it('stores selected regulation tags when creating an SOP document', function (): void {
    $department = Department::factory()->create(['name' => 'Quality Assurance', 'code' => 'QA']);
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $whoGmp = RegulationTag::factory()->create(['name' => 'WHO GMP', 'code' => 'WHO_GMP']);
    $usFda = RegulationTag::factory()->create(['name' => 'US FDA 210 & 211', 'code' => 'US_FDA_210_211']);
    $dpco = RegulationTag::factory()->create(['name' => 'India DPCO', 'code' => 'INDIA_DPCO']);

    $documentType = DocumentType::factory()->create();
    $documentType->regulationTags()->sync([$whoGmp->id, $usFda->id, $dpco->id]);

    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => $documentType->category_id,
        'document_type_id' => $documentType->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);
    $template->regulationTags()->sync([$whoGmp->id, $usFda->id]);

    $version = SopTemplateVersion::factory()
        ->published()
        ->create([
            'sop_template_id' => $template->id,
            'version' => 1,
        ]);

    $version->sections()->create([
        'title' => 'Purpose',
        'section_order' => 1,
        'section_type' => 'rich_text',
        'content' => '<p>Controlled document for {{department}}.</p>',
        'is_required' => true,
    ]);

    $version->variables()->createMany([
        [
            'name' => 'department',
            'label' => 'Department',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DEPARTMENT),
            'required' => true,
        ],
        [
            'name' => 'document_number',
            'label' => 'Document Number',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DOCUMENT_NUMBER),
            'required' => true,
        ],
    ]);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
        templateId: $template->id,
        title: 'Tagged SOP',
        ownerId: $owner->id,
        createdBy: $creator->id,
        regulationTagIds: [$whoGmp->id, $dpco->id],
        templateVersionId: $version->id,
        documentNumber: 'SOP-QA-00020',
    ));

    expect($document->regulationTags->pluck('id')->sort()->values()->all())
        ->toBe(collect([$whoGmp->id, $dpco->id])->sort()->values()->all());
});

it('logs template version publish changes in the audit log', function (): void {
    $user = User::factory()->create();

    $template = SopTemplate::factory()->create([
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);

    SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
    ]);

    $draftVersion = SopTemplateVersion::factory()->create([
        'sop_template_id' => $template->id,
        'version' => 2,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
        'change_reason' => 'Updated safety steps',
    ]);

    app(PublishTemplateAction::class)->execute($template, $user->id, 'Updated safety steps');

    expect(SopAuditLog::query()->where('sop_template_id', $template->id)->latest('id')->first())
        ->action->toBe(SopAuditLog::ACTION_VERSION_PUBLISHED)
        ->new_values->toMatchArray([
            'template_id' => $template->id,
            'template_version_id' => $draftVersion->id,
            'version' => 2,
            'change_reason' => 'Updated safety steps',
        ]);
});

it('renders the printable SOP document page for authorized users', function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('View:SopDocument', 'web');
    $user->givePermissionTo('View:SopDocument');

    $template = SopTemplate::factory()->create([
        'document_type_id' => DocumentType::factory()->create(['code' => 'SOP-PRINT'])->id,
    ]);
    $version = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
    ]);

    $document = SopDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $version->id,
        'department_id' => $template->department_id,
        'document_number' => 'SOP-QA-00002',
        'title' => 'Packaging SOP',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);

    $document->sections()->create([
        'title' => 'Procedure',
        'section_order' => 1,
        'content' => '<p>Follow approved packaging steps.</p>',
    ]);

    actingAs($user);

    get(route('sop-documents.print', $document))
        ->assertOk()
        ->assertSee('Packaging SOP')
        ->assertSee('SOP-QA-00002')
        ->assertSee('Follow approved packaging steps.', false);
});
