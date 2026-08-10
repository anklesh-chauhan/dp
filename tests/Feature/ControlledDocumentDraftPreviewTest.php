<?php

declare(strict_types=1);

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentVariable;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\ReportTemplate;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
});

/**
 * @return array{0: DocumentTemplate, 1: DocumentTemplateVersion}
 */
function draftPreviewTemplate(?int $reportTemplateId): array
{
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->id,
        'report_template_id' => $reportTemplateId,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
    ]);

    return [$template, $templateVersion];
}

it('renders a draft controlled document with the selected print template layout', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $reportTemplate = ReportTemplate::factory()->create([
        'name' => 'QA Print Layout',
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
        'created_by' => $user->getKey(),
        'updated_by' => $user->getKey(),
    ]);
    [$template, $templateVersion] = draftPreviewTemplate($reportTemplate->getKey());
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_number' => 'SOP-QA-1001',
        'title' => 'Equipment Cleaning Procedure',
        'purpose' => 'Describe cleaning requirements.',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $user->getKey(),
        'owner_id' => $user->getKey(),
    ]);

    ControlledDocumentSection::factory()->create([
        'document_id' => $document->getKey(),
        'title' => 'Responsibilities',
        'section_order' => 1,
        'content' => '<p>Follow this procedure.</p>',
    ]);
    ControlledDocumentVariable::factory()->create([
        'document_id' => $document->getKey(),
        'variable_name' => 'owner_name',
        'value' => 'Quality Assurance',
    ]);

    get(route('controlled-documents.draft-preview', $document))
        ->assertOk()
        ->assertSee('Equipment Cleaning Procedure')
        ->assertSee('SOP-QA-1001')
        ->assertSee('Responsibilities')
        ->assertSee('Follow this procedure.', false)
        ->assertSee('Quality Assurance')
        ->assertSee($reportTemplate->layout_key)
        ->assertDontSee('Draft Preview');
});

it('uses the print template saved on the source document template for layout', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

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
    [$template, $templateVersion] = draftPreviewTemplate($reportTemplate->getKey());
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $user->getKey(),
        'owner_id' => $user->getKey(),
    ]);

    ControlledDocumentSection::factory()->create([
        'document_id' => $document->getKey(),
        'title' => 'Hidden by current print layout',
        'content' => '<p>Current version content.</p>',
    ]);
    ControlledDocumentVariable::factory()->create([
        'document_id' => $document->getKey(),
        'variable_name' => 'hidden_variable',
        'value' => 'Hidden Variable Label',
    ]);

    get(route('controlled-documents.draft-preview', $document))
        ->assertOk()
        ->assertSee('Current User Print Sections')
        ->assertSee('Current version content.', false)
        ->assertSee('#9a3412')
        ->assertSee($reportTemplate->layout_key)
        ->assertDontSee('Hidden Variable Label')
        ->assertDontSee('Hidden by current print layout');
});

it('allows an explicit print template query override', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $defaultTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
        'created_by' => $user->getKey(),
        'updated_by' => $user->getKey(),
    ]);
    $overrideFields = collect(app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument))
        ->map(function (array $field): array {
            if ($field['key'] === 'sections') {
                $field['label'] = 'Override Print Sections';
            }

            return $field;
        })
        ->all();
    $overrideTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => $overrideFields,
        'created_by' => $user->getKey(),
        'updated_by' => $user->getKey(),
    ]);
    [$template, $templateVersion] = draftPreviewTemplate($defaultTemplate->getKey());
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $user->getKey(),
        'owner_id' => $user->getKey(),
    ]);

    ControlledDocumentSection::factory()->create([
        'document_id' => $document->getKey(),
        'content' => '<p>Override content.</p>',
    ]);

    get(route('controlled-documents.draft-preview', [
        'controlledDocument' => $document,
        'template' => $overrideTemplate->getKey(),
    ]))
        ->assertOk()
        ->assertSee('Override Print Sections')
        ->assertSee('Override content.', false)
        ->assertSee($overrideTemplate->layout_key)
        ->assertDontSee($defaultTemplate->layout_key);
});

it('requires a selected print template before previewing', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    [$template, $templateVersion] = draftPreviewTemplate(null);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'created_by' => $user->getKey(),
        'owner_id' => $user->getKey(),
    ]);

    get(route('controlled-documents.draft-preview', $document))
        ->assertStatus(422);
});

it('keeps preview available after an intermediate approval while later steps are pending', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $reportTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
        'created_by' => $user->getKey(),
        'updated_by' => $user->getKey(),
    ]);
    [$template, $templateVersion] = draftPreviewTemplate($reportTemplate->getKey());
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail()->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        'created_by' => $user->getKey(),
        'owner_id' => $user->getKey(),
    ]);

    expect($document->canBePrintedDirectly())->toBeFalse();

    Livewire::test(ViewControlledDocument::class, ['record' => $document->getRouteKey()])
        ->assertActionVisible('previewWithPrintTemplate')
        ->assertActionHidden('printPdf');

    get(route('controlled-documents.draft-preview', $document))
        ->assertOk()
        ->assertSee($reportTemplate->layout_key);
});

it('hides print-template preview once a non-issuable document can use the controlled PDF viewer', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Gate::before(static fn (): bool => true);

    $reportTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
        'created_by' => $user->getKey(),
        'updated_by' => $user->getKey(),
    ]);
    [$template, $templateVersion] = draftPreviewTemplate($reportTemplate->getKey());
    $nonIssuableType = DocumentType::query()->where('is_issuable', false)->first()
        ?? DocumentType::factory()->create(['is_issuable' => false, 'code' => 'POLICY-TEST', 'name' => 'Policy Test']);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_type_id' => $nonIssuableType->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::APPROVED),
        'created_by' => $user->getKey(),
        'owner_id' => $user->getKey(),
    ]);

    expect($document->fresh(['documentType', 'documentStatus'])->canBePrintedDirectly())->toBeTrue();

    Livewire::test(ViewControlledDocument::class, ['record' => $document->getRouteKey()])
        ->assertActionHidden('previewWithPrintTemplate')
        ->assertActionVisible('printPdf');
});
