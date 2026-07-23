<?php

declare(strict_types=1);

namespace App\Domain\SopTemplate\AI\Services;

use App\Domain\SopTemplate\AI\Providers\SopTemplateRuleProvider;
use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysis;
use App\Domain\SopTemplate\AI\Support\PlaceholderExtractor;
use App\Foundation\AI\Validation\Contracts\ValidationEngine;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

final readonly class SopTemplateIntegrityService
{
    public function __construct(
        private ValidationEngine $validationEngine,
        private SopTemplateRuleProvider $ruleProvider,
        private PlaceholderExtractor $placeholderExtractor,
    ) {
    }

    public function validate(
        array $generatedTemplate,
    ): ValidationResult {
        $analysis = GeneratedTemplateAnalysis::analyze(
            $generatedTemplate,
            $this->placeholderExtractor,
        );

        $context = new ValidationContext(
            artifactType: 'sop_template',
            attributes: [
                'analysis' => $analysis,
            ],
        );

        return $this->validationEngine->validate(
            artifact: $generatedTemplate,
            context: $context,
            rules: $this->ruleProvider->rules(),
        );
    }
}
