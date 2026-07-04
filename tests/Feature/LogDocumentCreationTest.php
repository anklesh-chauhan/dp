<?php

declare(strict_types=1);

use App\Enums\VariableDataType;
use App\Filament\Support\TemplateVariableFieldBuilder;
use App\Models\SopTemplateVariable;

it('excludes auto-populated log document variables from the create form', function (): void {
    $departmentVariable = new SopTemplateVariable([
        'name' => 'department',
        'label' => 'Department',
        'datatype' => VariableDataType::Department,
        'required' => true,
    ]);

    $documentNumberVariable = new SopTemplateVariable([
        'name' => 'document_number',
        'label' => 'Document Number',
        'datatype' => VariableDataType::Text,
        'required' => true,
    ]);

    $referencedSopVariable = new SopTemplateVariable([
        'name' => 'referenced_sop',
        'label' => 'Referenced SOP',
        'datatype' => VariableDataType::SopReference,
        'required' => true,
    ]);

    $logExclusions = ['department', 'batch_number', 'product_name', 'referenced_sop'];

    expect(TemplateVariableFieldBuilder::shouldExcludeFromForm($departmentVariable, $logExclusions))->toBeTrue()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($documentNumberVariable, $logExclusions))->toBeTrue()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($referencedSopVariable, $logExclusions))->toBeTrue();
});

it('shows department and sop reference selects on standard sop create forms', function (): void {
    $departmentVariable = new SopTemplateVariable([
        'name' => 'department',
        'label' => 'Department',
        'datatype' => VariableDataType::Department,
        'required' => true,
    ]);

    $referencedSopVariable = new SopTemplateVariable([
        'name' => 'referenced_sop',
        'label' => 'Referenced SOP',
        'datatype' => VariableDataType::SopReference,
        'required' => true,
    ]);

    expect(TemplateVariableFieldBuilder::shouldExcludeFromForm($departmentVariable))->toBeFalse()
        ->and(TemplateVariableFieldBuilder::shouldExcludeFromForm($referencedSopVariable))->toBeFalse();
});
