<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Rules\UniqueVariableNamesRule;
use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use Tests\Support\CreatesGeneratedTemplateValidationContext;

uses(CreatesGeneratedTemplateValidationContext::class);

describe('UniqueVariableNamesRule', function (): void {

    it('passes when every variable name is unique', function (): void {
        $template = [
            'sections' => [],
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'expiry_date'],
                ['name' => 'operator_name'],
            ],
        ];

        $rule = new UniqueVariableNamesRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toBeEmpty();
    });

    it('reports duplicate variable names', function (): void {
        $template = [
            'sections' => [],
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'expiry_date'],
                ['name' => 'batch_number'],
            ],
        ];

        $rule = new UniqueVariableNamesRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toHaveCount(1);

        $issue = $issues->all()[0];

        expect($issue->code())
            ->toBe('unique_variable_names')
            ->and($issue->severity())
            ->toBe(ValidationSeverity::ERROR)
            ->and($issue->path())
            ->toBe('variables[2].name')
            ->and($issue->metadata()['variable'])
            ->toBe('batch_number')
            ->and($issue->metadata()['first_index'])
            ->toBe(0)
            ->and($issue->metadata()['duplicate_index'])
            ->toBe(2);
    });

    it('reports every duplicate occurrence', function (): void {
        $template = [
            'sections' => [],
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'batch_number'],
                ['name' => 'batch_number'],
                ['name' => 'batch_number'],
            ],
        ];

        $rule = new UniqueVariableNamesRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toHaveCount(3);

        $duplicates = $issues->all();

        expect($duplicates[0]->path())->toBe('variables[1].name');
        expect($duplicates[1]->path())->toBe('variables[2].name');
        expect($duplicates[2]->path())->toBe('variables[3].name');

        foreach ($duplicates as $issue) {
            expect($issue->code())->toBe('unique_variable_names');
            expect($issue->severity())->toBe(ValidationSeverity::ERROR);
            expect($issue->metadata()['first_index'])->toBe(0);
        }
    });
});
