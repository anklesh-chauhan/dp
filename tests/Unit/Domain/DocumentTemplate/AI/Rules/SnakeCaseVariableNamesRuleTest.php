<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Rules\SnakeCaseVariableNamesRule;
use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use Tests\Support\CreatesGeneratedTemplateValidationContext;

uses(CreatesGeneratedTemplateValidationContext::class);

describe('SnakeCaseVariableNamesRule', function (): void {

    it('passes when every variable uses snake_case', function (): void {
        $template = [
            'sections' => [],
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'expiry_date'],
                ['name' => 'operator_name'],
            ],
        ];

        $rule = new SnakeCaseVariableNamesRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toBeEmpty();
    });

    it('reports variables that are not snake_case', function (): void {
        $template = [
            'sections' => [],
            'variables' => [
                ['name' => 'BatchNumber'],
                ['name' => 'expiry_date'],
                ['name' => 'OperatorName'],
            ],
        ];

        $rule = new SnakeCaseVariableNamesRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toHaveCount(2);

        $all = $issues->all();

        expect($all[0]->code())
            ->toBe('snake_case_variable_names')
            ->and($all[0]->severity())
            ->toBe(ValidationSeverity::ERROR)
            ->and($all[0]->path())
            ->toBe('variables[0].name')
            ->and($all[0]->metadata()['variable'])
            ->toBe('BatchNumber');

        expect($all[1]->code())
            ->toBe('snake_case_variable_names')
            ->and($all[1]->severity())
            ->toBe(ValidationSeverity::ERROR)
            ->and($all[1]->path())
            ->toBe('variables[2].name')
            ->and($all[1]->metadata()['variable'])
            ->toBe('OperatorName');
    });

    it('accepts valid snake_case variable names', function (): void {
        $template = [
            'sections' => [],
            'variables' => [
                ['name' => 'batch_1'],
                ['name' => 'operator_name_2'],
                ['name' => 'sample_id'],
            ],
        ];

        $rule = new SnakeCaseVariableNamesRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toBeEmpty();
    });

});
