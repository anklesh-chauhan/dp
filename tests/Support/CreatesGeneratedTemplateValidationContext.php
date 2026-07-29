<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysis;
use App\Domain\DocumentTemplate\AI\Support\PlaceholderExtractor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

trait CreatesGeneratedTemplateValidationContext
{
    protected function createValidationContext(
        array $template,
    ): ValidationContext {
        $analysis = GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor,
        );

        return new ValidationContext(
            artifactType: 'document_template',
            attributes: [
                'analysis' => $analysis,
            ],
        );
    }
}
