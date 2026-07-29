<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Rules\EveryVariableMustBeReferencedRule;
use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use Tests\Support\CreatesGeneratedTemplateValidationContext;

uses(CreatesGeneratedTemplateValidationContext::class);

describe('EveryVariableMustBeReferencedRule', function (): void {

    it('passes when every variable is referenced', function (): void {
        $template = [
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'expiry_date'],
            ],
            'sections' => [
                [
                    'content' => '{{batch_number}} {{expiry_date}}',
                ],
            ],
        ];

        $rule = new EveryVariableMustBeReferencedRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toBeEmpty();
    });

    it('reports variables that are never referenced', function (): void {
        $template = [
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'expiry_date'],
                ['name' => 'operator_name'],
            ],
            'sections' => [
                [
                    'content' => '{{batch_number}} {{expiry_date}}',
                ],
            ],
        ];

        $rule = new EveryVariableMustBeReferencedRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toHaveCount(1);

        $issue = $issues->all()[0];

        expect($issue->code())
            ->toBe('referenced_variables')
            ->and($issue->severity())
            ->toBe(ValidationSeverity::ERROR)
            ->and($issue->path())
            ->toBe('variables[2].name')
            ->and($issue->metadata()['variable'])
            ->toBe('operator_name')
            ->and($issue->metadata()['index'])
            ->toBe(2);
    });

    it('reports every unreferenced variable', function (): void {
        $template = [
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'expiry_date'],
                ['name' => 'operator_name'],
                ['name' => 'reviewer_name'],
            ],
            'sections' => [
                [
                    'content' => '{{batch_number}}',
                ],
            ],
        ];

        $rule = new EveryVariableMustBeReferencedRule(
            new GeneratedTemplateAnalysisResolver,
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toHaveCount(3);

        $variables = array_map(
            static fn ($issue) => $issue->metadata()['variable'],
            $issues->all(),
        );

        expect($variables)
            ->toContain('expiry_date')
            ->toContain('operator_name')
            ->toContain('reviewer_name');

        foreach ($issues->all() as $issue) {
            expect($issue->severity())
                ->toBe(ValidationSeverity::ERROR);

            expect($issue->code())
                ->toBe('referenced_variables');
        }
    });
});
