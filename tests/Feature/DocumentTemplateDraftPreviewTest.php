<?php

declare(strict_types=1);

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\ReportTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\VariableDataType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the latest draft template version in the draft preview', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $draftStatus = TemplateStatus::query()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);
    VariableDataType::query()->create([
        'code' => VariableDataType::TEXT,
        'name' => 'Text',
        'sort_order' => 1,
    ]);
    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'department_id' => Department::factory(),
        'template_status_id' => $draftStatus->getKey(),
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
        'template_status_id' => $draftStatus->getKey(),
        'version' => 1,
    ]);

    DocumentTemplateSection::factory()->create([
        'template_version_id' => $version->getKey(),
        'title' => 'Responsibilities',
        'content' => '<p>Follow this procedure.</p>',
    ]);
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version->getKey(),
        'name' => 'owner_name',
        'label' => 'Owner',
        'default_value' => 'Quality Assurance',
    ]);

    get(route('document-templates.draft-preview', $template))
        ->assertOk()
        ->assertSee('Draft Preview')
        ->assertSee('Responsibilities')
        ->assertSee('Follow this procedure.', false)
        ->assertSee('Quality Assurance');
});

it('uses the print template saved on the document template for its preview layout', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $draftStatus = TemplateStatus::query()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);
    VariableDataType::query()->create([
        'code' => VariableDataType::TEXT,
        'name' => 'Text',
        'sort_order' => 1,
    ]);

    $fields = collect(app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument))
        ->map(function (array $field): array {
            if ($field['key'] === 'variables') {
                $field['enabled'] = false;
            }

            if ($field['key'] === 'sections') {
                $field['label'] = 'Current User Print Sections';
                $field['show_section_titles'] = false;
            }

            return $field;
        })
        ->all();
    $pageSettings = app(PrintLayoutRegistry::class)->defaultPageSettings();
    $pageSettings['primary_color'] = '#9a3412';

    $reportTemplate = ReportTemplate::factory()->create([
        'name' => 'QA Current Printing Template',
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => $fields,
        'page_settings' => $pageSettings,
        'created_by' => $user->getKey(),
        'updated_by' => $user->getKey(),
    ]);
    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'department_id' => Department::factory(),
        'report_template_id' => $reportTemplate->getKey(),
        'template_status_id' => $draftStatus->getKey(),
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
        'template_status_id' => $draftStatus->getKey(),
    ]);
    DocumentTemplateSection::factory()->create([
        'template_version_id' => $version->getKey(),
        'title' => 'Hidden by current print layout',
        'content' => '<p>Current version content.</p>',
    ]);
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version->getKey(),
        'name' => 'hidden_variable',
        'label' => 'Hidden Variable Label',
    ]);

    get(route('document-templates.versions.preview', [$template, $version]))
        ->assertOk()
        ->assertSee('Version Preview')
        ->assertSee('QA Current Printing Template')
        ->assertSee('Current User Print Sections')
        ->assertSee('Current version content.', false)
        ->assertSee('#9a3412')
        ->assertDontSee('Hidden Variable Label')
        ->assertDontSee('Hidden by current print layout');
});

it('previews the requested template version instead of replacing it with the latest draft', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $draftStatus = TemplateStatus::query()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);
    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'department_id' => Department::factory(),
        'template_status_id' => $draftStatus->getKey(),
    ]);
    $reviewedVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
        'template_status_id' => $draftStatus->getKey(),
        'version' => 1,
    ]);
    $latestDraft = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
        'template_status_id' => $draftStatus->getKey(),
        'version' => 2,
    ]);
    DocumentTemplateSection::factory()->create([
        'template_version_id' => $reviewedVersion->getKey(),
        'title' => 'Version One Review Content',
    ]);
    DocumentTemplateSection::factory()->create([
        'template_version_id' => $latestDraft->getKey(),
        'title' => 'Version Two Draft Content',
    ]);

    get(route('document-templates.versions.preview', [$template, $reviewedVersion]))
        ->assertOk()
        ->assertSee('Version One Review Content')
        ->assertDontSee('Version Two Draft Content');
});

it('does not preview a version that belongs to another document template', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $draftStatus = TemplateStatus::query()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);
    $template = DocumentTemplate::factory()->create([
        'template_status_id' => $draftStatus->getKey(),
    ]);
    $otherTemplate = DocumentTemplate::factory()->create([
        'department_id' => $template->department_id,
        'category_id' => $template->category_id,
        'document_type_id' => $template->document_type_id,
        'template_status_id' => $draftStatus->getKey(),
    ]);
    $otherVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $otherTemplate->getKey(),
        'template_status_id' => $draftStatus->getKey(),
    ]);

    get(route('document-templates.versions.preview', [$template, $otherVersion]))
        ->assertNotFound();
});
