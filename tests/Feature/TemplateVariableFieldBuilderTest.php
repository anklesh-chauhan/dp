<?php

declare(strict_types=1);

use App\Filament\Support\TemplateVariableFieldBuilder;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('excludes document lifecycle dates from template variable forms', function (): void {
    $draftStatus = TemplateStatus::query()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);
    $dateType = VariableDataType::query()->create([
        'code' => VariableDataType::DATE,
        'name' => 'Date',
        'sort_order' => 1,
    ]);
    $textType = VariableDataType::query()->create([
        'code' => VariableDataType::TEXT,
        'name' => 'Text',
        'sort_order' => 2,
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'template_status_id' => $draftStatus->id,
    ]);

    $effectiveDate = DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version->id,
        'name' => 'effective_date',
        'variable_data_type_id' => $dateType->id,
    ]);
    $reviewDate = DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version->id,
        'name' => 'review_date',
        'variable_data_type_id' => $dateType->id,
    ]);
    $equipment = DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version->id,
        'name' => 'equipment',
        'variable_data_type_id' => $textType->id,
    ]);

    expect(TemplateVariableFieldBuilder::shouldExcludeFromForm($effectiveDate->load('variableDataType')))->toBeTrue()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($reviewDate->load('variableDataType')))->toBeTrue()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($equipment->load('variableDataType')))->toBeFalse();
});
