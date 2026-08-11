<?php

declare(strict_types=1);

use App\Domain\DMS\Services\SopReferenceService;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);

    $this->service = app(SopReferenceService::class);
});

it('formats sop option labels with document number and title', function (): void {
    $sop = new ControlledDocument([
        'document_number' => 'SOP-QA-00042',
        'title' => 'Equipment Cleaning Procedure',
    ]);

    expect(app(SopReferenceService::class)->formatOptionLabel($sop))
        ->toBe('SOP-QA-00042 - Equipment Cleaning Procedure');
});

it('returns effective department sops with document number and title labels', function (): void {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $sopType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $formType = DocumentType::query()->where('code', DocumentType::FORM)->firstOrFail();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $formType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);
    $sopTemplate = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $sopType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);
    $sopTemplateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $sopTemplate,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);

    $effectiveSop = ControlledDocument::factory()->create([
        'template_id' => $sopTemplate,
        'template_version_id' => $sopTemplateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $sopType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'document_number' => 'SOP-QA-00001',
        'title' => 'Cleaning Validation',
    ]);

    ControlledDocument::factory()->create([
        'template_id' => $sopTemplate,
        'template_version_id' => $sopTemplateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $sopType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'document_number' => 'SOP-QA-00002',
        'title' => 'Draft SOP',
    ]);

    ControlledDocument::factory()->create([
        'template_id' => $sopTemplate,
        'template_version_id' => $sopTemplateVersion,
        'department_id' => $otherDepartment,
        'category_id' => $category,
        'document_type_id' => $sopType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'document_number' => 'SOP-QA-00003',
        'title' => 'Other Department SOP',
    ]);

    expect($this->service->effectiveSopOptions($template->id))
        ->toBe([
            $effectiveSop->id => 'SOP-QA-00001 - Cleaning Validation',
        ]);
});

it('includes a currently selected non-effective sop in select options', function (): void {
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $sopType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $formType = DocumentType::query()->where('code', DocumentType::FORM)->firstOrFail();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $formType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);
    $sopTemplate = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $sopType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);
    $sopTemplateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $sopTemplate,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);

    $archivedSop = ControlledDocument::factory()->create([
        'template_id' => $sopTemplate,
        'template_version_id' => $sopTemplateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $sopType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::ARCHIVED),
        'document_number' => 'SOP-QA-00099',
        'title' => 'Retired Procedure',
    ]);

    expect($this->service->sopSelectOptions($template->id, $archivedSop->id))
        ->toBe([
            $archivedSop->id => 'SOP-QA-00099 - Retired Procedure',
        ]);
});
