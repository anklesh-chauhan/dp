<?php

declare(strict_types=1);

use App\Domain\SopTemplate\AI\Providers\SopTemplateRuleProvider;
use App\Domain\SopTemplate\AI\Services\SopTemplateIntegrityService;
use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Domain\SopTemplate\AI\Support\PlaceholderExtractor;
use App\Foundation\AI\Validation\DefaultValidationEngine;
use App\Foundation\AI\Validation\Pipeline\RuleExecutor;
use App\Foundation\AI\Validation\Pipeline\ValidationPipeline;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;


describe('SopTemplateIntegrityService', function (): void {

    it('returns a passing validation result for a valid template', function (): void {
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

        $result = createIntegrityService()->validate($template);

        expect($result)
            ->toBeInstanceOf(ValidationResult::class);

        expect($result->passed())->toBeTrue();
        expect($result->failed())->toBeFalse();
        expect($result->issues()->isEmpty())->toBeTrue();
        expect($result->hasErrors())->toBeFalse();
        expect($result->hasWarnings())->toBeFalse();
    });

    it('returns a failing validation result for an invalid template', function (): void {
        $template = [
            'variables' => [
                ['name' => 'BatchNumber'],
            ],
            'sections' => [
                [
                    'content' => '{{expiry_date}}',
                ],
            ],
        ];

        $result = createIntegrityService()->validate($template);

        expect($result)
            ->toBeInstanceOf(ValidationResult::class);

        expect($result->passed())->toBeFalse();
        expect($result->failed())->toBeTrue();
        expect($result->hasErrors())->toBeTrue();
        expect($result->issues()->isNotEmpty())->toBeTrue();

        $codes = array_map(
            static fn ($issue) => $issue->code(),
            $result->issues()->all(),
        );

        expect($codes)
            ->toContain('snake_case_variable_names')
            ->toContain('defined_placeholders')
            ->toContain('referenced_variables');
    });
});
