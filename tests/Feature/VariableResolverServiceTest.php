<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\VariableDataType;
use App\Services\Sop\SopReferenceService;
use App\Services\Sop\VariableResolverService;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
});

it('resolves variable values into separate storage and substitution maps', function (): void {
    $department = Department::factory()->create(['name' => 'Quality Assurance']);
    $version = SopTemplateVersion::factory()->create();

    $version->variables()->createMany([
        [
            'name' => 'department',
            'label' => 'Department',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DEPARTMENT),
            'required' => true,
        ],
        [
            'name' => 'equipment',
            'label' => 'Equipment',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
            'required' => true,
        ],
        [
            'name' => 'requires_shutdown',
            'label' => 'Requires Shutdown',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::BOOLEAN),
            'required' => true,
        ],
    ]);

    $resolved = app(VariableResolverService::class)->resolveValues($version, [
        'department' => $department->id,
        'equipment' => 'Mixer',
        'requires_shutdown' => true,
    ]);

    expect($resolved['storage'])->toMatchArray([
        'department' => (string) $department->id,
        'equipment' => 'Mixer',
        'requires_shutdown' => '1',
    ])->and($resolved['substitution'])->toMatchArray([
        'department' => 'Quality Assurance',
        'equipment' => 'Mixer',
        'requires_shutdown' => 'Yes',
    ]);
});

it('validates required template variables', function (): void {
    $version = SopTemplateVersion::factory()->create();

    $version->variables()->create([
        'name' => 'equipment',
        'label' => 'Equipment',
        'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
        'required' => true,
    ]);

    app(VariableResolverService::class)->resolveValues($version, []);
})->throws(ValidationException::class);

it('resolves nested variable placeholders within stored values', function (): void {
    $version = SopTemplateVersion::factory()->create();

    $version->variables()->createMany([
        [
            'name' => 'document_number',
            'label' => 'Document Number',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
            'required' => true,
        ],
        [
            'name' => 'reference_line',
            'label' => 'Reference Line',
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
            'default_value' => 'Controlled copy of {{document_number}}',
            'required' => false,
        ],
    ]);

    $resolved = app(VariableResolverService::class)->resolveValues($version, [
        'document_number' => 'SOP-QA-00001',
    ]);

    expect($resolved['storage']['reference_line'])->toBe('Controlled copy of SOP-QA-00001')
        ->and($resolved['substitution']['reference_line'])->toBe('Controlled copy of SOP-QA-00001');
});

it('replaces placeholders in section content', function (): void {
    $resolver = app(VariableResolverService::class);

    $content = $resolver->replace(
        '<p>{{equipment}} for {{department}} on {{inspection_date}}.</p>',
        [
            'equipment' => 'Mixer',
            'department' => 'Quality Assurance',
            'inspection_date' => '2026-06-28',
        ],
    );

    expect($content)->toBe('<p>Mixer for Quality Assurance on 2026-06-28.</p>');
});

it('returns effective sop options scoped to the template department', function (): void {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $sopTypeId = DocumentType::factory()->create(['code' => DocumentType::SOP])->id;
    $effectiveStatusId = DocumentStatus::idFor(DocumentStatus::EFFECTIVE);

    $template = SopTemplate::factory()->create(['department_id' => $department->id]);

    $matchingSop = SopDocument::factory()->create([
        'department_id' => $department->id,
        'document_type_id' => $sopTypeId,
        'document_status_id' => $effectiveStatusId,
        'document_number' => 'SOP-QA-00001',
    ]);

    SopDocument::factory()->create([
        'department_id' => $otherDepartment->id,
        'document_type_id' => $sopTypeId,
        'document_status_id' => $effectiveStatusId,
        'document_number' => 'SOP-PROD-00001',
    ]);

    $options = app(SopReferenceService::class)->effectiveSopOptions($template->id);

    expect($options)->toBe([
        $matchingSop->id => 'SOP-QA-00001',
    ]);
});
