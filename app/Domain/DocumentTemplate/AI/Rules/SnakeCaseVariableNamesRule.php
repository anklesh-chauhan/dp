<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\AI\Rules;

use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class SnakeCaseVariableNamesRule implements ValidationRule
{
    public function __construct(
        private GeneratedTemplateAnalysisResolver $analysisResolver,
    ) {}

    public function code(): string
    {
        return 'snake_case_variable_names';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        $analysis = $this->analysisResolver->resolve($context);

        $issues = new ValidationIssueCollection;

        foreach ($analysis->variables() as $index => $variable) {
            $name = $variable['name'] ?? null;

            if (! is_string($name)) {
                continue;
            }

            if (preg_match('/^[a-z][a-z0-9_]*$/', $name) === 1) {
                continue;
            }

            $issues = $issues->with(
                ValidationIssueData::error(
                    code: $this->code(),
                    message: sprintf(
                        'Variable "%s" must use snake_case.',
                        $name,
                    ),
                    path: sprintf(
                        'variables[%d].name',
                        $index,
                    ),
                    metadata: [
                        'variable' => $name,
                    ],
                ),
            );
        }

        return $issues;
    }
}
