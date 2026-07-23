<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysis;
use App\Domain\SopTemplate\AI\Support\PlaceholderExtractor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

trait CreatesGeneratedTemplateValidationContext
{
    protected function createValidationContext(
        array $template,
    ): ValidationContext {
        $analysis = GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        );

        return new ValidationContext(
            artifactType: 'sop_template',
            attributes: [
                'analysis' => $analysis,
            ],
        );
    }
}
