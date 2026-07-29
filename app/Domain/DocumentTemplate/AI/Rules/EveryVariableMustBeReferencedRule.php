<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\AI\Rules;

use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class EveryVariableMustBeReferencedRule implements ValidationRule
{
    public function __construct(
        private GeneratedTemplateAnalysisResolver $analysisResolver,
    ) {}

    public function code(): string
    {
        return 'referenced_variables';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        $analysis = $this->analysisResolver->resolve($context);

        $issues = new ValidationIssueCollection;

        $referencedPlaceholders = array_flip(
            $analysis->placeholderNames(),
        );

        foreach ($analysis->variables() as $index => $variable) {
            $name = $variable['name'];

            if (isset($referencedPlaceholders[$name])) {
                continue;
            }

            $issues = $issues->with(
                ValidationIssueData::error(
                    code: $this->code(),
                    message: sprintf(
                        'Variable "%s" is defined but never referenced.',
                        $name,
                    ),
                    path: sprintf(
                        'variables[%d].name',
                        $index,
                    ),
                    metadata: [
                        'variable' => $name,
                        'index' => $index,
                    ],
                ),
            );
        }

        return $issues;
    }
}
