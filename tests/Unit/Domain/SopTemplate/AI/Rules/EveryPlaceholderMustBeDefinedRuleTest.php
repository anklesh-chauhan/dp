<?php

declare(strict_types=1);

use App\Domain\SopTemplate\AI\Rules\EveryPlaceholderMustBeDefinedRule;
use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use Tests\Support\CreatesGeneratedTemplateValidationContext;

uses(CreatesGeneratedTemplateValidationContext::class);

describe('EveryPlaceholderMustBeDefinedRule', function (): void {

    it('passes when every placeholder has a matching variable', function (): void {
        $template = [
            'variables' => [
                ['name' => 'batch_number'],
                ['name' => 'expiry_date'],
            ],
            'sections' => [
                [
                    'content' => 'Batch {{batch_number}} expires {{expiry_date}}',
                ],
            ],
        ];

        $rule = new EveryPlaceholderMustBeDefinedRule(
            new GeneratedTemplateAnalysisResolver(),
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toBeEmpty();
    });

    it('reports placeholders without matching variables', function (): void {
        $template = [
            'variables' => [
                ['name' => 'batch_number'],
            ],
            'sections' => [
                [
                    'content' => 'Batch {{batch_number}} expires {{expiry_date}}',
                ],
            ],
        ];

        $rule = new EveryPlaceholderMustBeDefinedRule(
            new GeneratedTemplateAnalysisResolver(),
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toHaveCount(1);

        $issue = $issues->all()[0];

        expect($issue->code())
            ->toBe('defined_placeholders')
            ->and($issue->severity())
            ->toBe(ValidationSeverity::ERROR)
            ->and($issue->metadata()['placeholder'])
            ->toBe('expiry_date');
    });

    it('reports every undefined placeholder', function (): void {
        $template = [
            'variables' => [
                ['name' => 'batch_number'],
            ],
            'sections' => [
                [
                    'content' => '{{batch_number}} {{expiry_date}} {{operator_name}}',
                ],
            ],
        ];

        $rule = new EveryPlaceholderMustBeDefinedRule(
            new GeneratedTemplateAnalysisResolver(),
        );

        $issues = $rule->validate(
            $template,
            $this->createValidationContext($template),
        );

        expect($issues)->toHaveCount(2);

        $placeholders = array_map(
            static fn ($issue) => $issue->metadata()['placeholder'],
            $issues->all(),
        );

        expect($placeholders)
            ->toContain('expiry_date')
            ->toContain('operator_name');
    });
});
