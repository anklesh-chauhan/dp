<?php

declare(strict_types=1);

namespace App\Domain\SopTemplate\AI\Rules;

use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class EveryPlaceholderMustBeDefinedRule implements ValidationRule
{
    public function __construct(
        private GeneratedTemplateAnalysisResolver $analysisResolver,
    ) {
    }

    public function code(): string
    {
        return 'defined_placeholders';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        $analysis = $this->analysisResolver->resolve($context);

        $issues = new ValidationIssueCollection();

        $definedVariables = array_flip(
            $analysis->variableNames(),
        );

        foreach ($analysis->placeholderNames() as $placeholder) {
            if (isset($definedVariables[$placeholder])) {
                continue;
            }

            $issues = $issues->with(
                ValidationIssueData::error(
                    code: $this->code(),
                    message: sprintf(
                        'Placeholder "%s" has no matching variable definition.',
                        $placeholder,
                    ),
                    metadata: [
                        'placeholder' => $placeholder,
                    ],
                ),
            );
        }

        return $issues;
    }
}
