<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\SopTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function typedTemplateVariable(string $name, string $label, string $typeCode, array $attributes = []): SopTemplateVariable
{
    $variable = new SopTemplateVariable(array_merge([
        'name' => $name,
        'label' => $label,
        'variable_data_type_id' => VariableDataType::idFor($typeCode),
        'required' => true,
    ], $attributes));

    $variable->setRelation('variableDataType', VariableDataType::findByCode($typeCode));

    return $variable;
}

it('registers handlers for all supported variable data type codes', function (): void {
    $registry = VariableTypeRegistry::default();

    $codes = [
        VariableDataType::TEXT,
        VariableDataType::LONG_TEXT,
        VariableDataType::RICH_TEXT,
        VariableDataType::INTEGER,
        VariableDataType::DECIMAL,
        VariableDataType::CURRENCY,
        VariableDataType::PERCENTAGE,
        VariableDataType::DATE,
        VariableDataType::DATETIME,
        VariableDataType::TIME,
        VariableDataType::BOOLEAN,
        VariableDataType::CHECKBOX,
        VariableDataType::SELECT,
        VariableDataType::MULTI_SELECT,
        VariableDataType::RADIO,
        VariableDataType::USER,
        VariableDataType::EMPLOYEE,
        VariableDataType::DEPARTMENT,
        VariableDataType::DESIGNATION,
        VariableDataType::SOP_REFERENCE,
        VariableDataType::SOP_DOCUMENT,
        VariableDataType::DOCUMENT_NUMBER,
        VariableDataType::FILE,
        VariableDataType::IMAGE,
        VariableDataType::URL,
        VariableDataType::EMAIL,
        VariableDataType::PHONE,
        VariableDataType::TEXTAREA,
        VariableDataType::NUMBER,
    ];

    foreach ($codes as $code) {
        expect($registry->forCode($code))->not->toBeNull();
    }
});

it('formats relationship and boolean values differently for storage and substitution', function (): void {
    $registry = app(VariableTypeRegistry::class);

    $departmentModel = Department::factory()->create(['name' => 'Quality Assurance']);
    $department = typedTemplateVariable('department', 'Department', VariableDataType::DEPARTMENT);
    $shutdown = typedTemplateVariable('requires_shutdown', 'Requires Shutdown', VariableDataType::BOOLEAN);

    expect($registry->formatForStorage($department, $departmentModel->id))->toBe((string) $departmentModel->id)
        ->and($registry->formatForSubstitution($department, $departmentModel->id))->toBe('Quality Assurance')
        ->and($registry->formatForStorage($shutdown, false))->toBe('0')
        ->and($registry->formatForSubstitution($shutdown, false))->toBe('No');
});

it('uses configured options for select substitution labels', function (): void {
    $registry = app(VariableTypeRegistry::class);

    $variable = typedTemplateVariable('batch_size', 'Batch Size', VariableDataType::SELECT, [
        'options' => [
            'small' => 'Small Batch',
            'large' => 'Large Batch',
        ],
    ]);

    expect($registry->formatForSubstitution($variable, 'large'))->toBe('Large Batch')
        ->and($registry->formatForStorage($variable, 'large'))->toBe('large');
});

it('stores multi select values as json', function (): void {
    $registry = app(VariableTypeRegistry::class);

    $variable = typedTemplateVariable('equipment', 'Equipment', VariableDataType::MULTI_SELECT, [
        'options' => [
            'mixer' => 'Mixer',
            'dryer' => 'Dryer',
        ],
    ]);

    expect($registry->formatForStorage($variable, ['mixer', 'dryer']))->toBe('["mixer","dryer"]')
        ->and($registry->formatForSubstitution($variable, ['mixer', 'dryer']))->toBe('Mixer, Dryer');
});
