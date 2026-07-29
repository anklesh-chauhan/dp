<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Rules\ValidVariableStructureRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

describe('ValidVariableStructureRule', function (): void {

    it('passes for structurally valid variables', function (): void {
        $issues = (new ValidVariableStructureRule)->validate([
            'variables' => [[
                'name' => 'effective_date',
                'label' => 'Effective Date',
                'datatype' => 'date',
                'default_value' => '',
                'required' => true,
            ]],
        ], new ValidationContext('document_template'));

        expect($issues)->toBeEmpty();
    });

    it('reports a missing variables collection', function (): void {
        $issues = (new ValidVariableStructureRule)->validate(
            [],
            new ValidationContext('document_template'),
        );

        expect($issues)->toHaveCount(1)
            ->and($issues->all()[0]->message())
            ->toBe('AI template generation returned invalid variables.')
            ->and($issues->all()[0]->path())
            ->toBe('variables');
    });

    it('reports missing and invalid variable fields with paths', function (): void {
        $issues = (new ValidVariableStructureRule)->validate([
            'variables' => [[
                'name' => '',
                'datatype' => 'text',
                'default_value' => [],
                'required' => 'yes',
            ]],
        ], new ValidationContext('document_template'));

        expect($issues->isNotEmpty())->toBeTrue()
            ->and($issues->all()[0]->path())
            ->toBe('variables[0].label');
    });
});
