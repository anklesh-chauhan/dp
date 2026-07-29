<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\AI\Support;

use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use RuntimeException;

final readonly class GeneratedTemplateAnalysisResolver
{
    public function resolve(
        ValidationContext $context,
    ): GeneratedTemplateAnalysis {
        $analysis = $context->get('analysis');

        if (! $analysis instanceof GeneratedTemplateAnalysis) {
            throw new RuntimeException(
                'Generated template analysis is missing from the validation context.',
            );
        }

        return $analysis;
    }
}
