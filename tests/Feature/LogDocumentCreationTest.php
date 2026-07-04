<?php

declare(strict_types=1);

use App\Filament\Support\TemplateVariableFieldBuilder;
use App\Models\SopTemplateVariable;
use App\Models\VariableDataType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function templateVariable(string $name, string $label, string $typeCode): SopTemplateVariable
{
    $variable = new SopTemplateVariable([
        'name' => $name,
        'label' => $label,
        'variable_data_type_id' => VariableDataType::idFor($typeCode),
        'required' => true,
    ]);

    $variable->setRelation('variableDataType', VariableDataType::findByCode($typeCode));

    return $variable;
}

it('excludes auto-populated log document variables from the create form', function (): void {
    $departmentVariable = templateVariable('department', 'Department', VariableDataType::DEPARTMENT);
    $documentNumberVariable = templateVariable('document_number', 'Document Number', VariableDataType::TEXT);
    $referencedSopVariable = templateVariable('referenced_sop', 'Referenced SOP', VariableDataType::SOP_REFERENCE);

    $logExclusions = ['department', 'batch_number', 'product_name', 'referenced_sop'];

    expect(TemplateVariableFieldBuilder::shouldExcludeFromForm($departmentVariable, $logExclusions))->toBeTrue()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($documentNumberVariable, $logExclusions))->toBeTrue()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($referencedSopVariable, $logExclusions))->toBeTrue();
});

it('shows department and sop reference selects on standard sop create forms', function (): void {
    $departmentVariable = templateVariable('department', 'Department', VariableDataType::DEPARTMENT);
    $referencedSopVariable = templateVariable('referenced_sop', 'Referenced SOP', VariableDataType::SOP_REFERENCE);

    expect(TemplateVariableFieldBuilder::shouldExcludeFromForm($departmentVariable))->toBeFalse()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($referencedSopVariable))->toBeFalse();
});
