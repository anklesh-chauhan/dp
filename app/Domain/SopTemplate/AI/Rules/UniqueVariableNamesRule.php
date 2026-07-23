<?php

declare(strict_types=1);

namespace App\Domain\SopTemplate\AI\Rules;

use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class UniqueVariableNamesRule implements ValidationRule
{
    public function __construct(
        private GeneratedTemplateAnalysisResolver $analysisResolver,
    ) {
    }

    public function code(): string
    {
        return 'unique_variable_names';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        $analysis = $this->analysisResolver->resolve($context);

        $issues = new ValidationIssueCollection();

        /** @var array<string, int> $seen */
        $seen = [];

        foreach ($analysis->variables() as $index => $variable) {
            $name = $variable['name'];

            if (! array_key_exists($name, $seen)) {
                $seen[$name] = $index;

                continue;
            }

            $issues = $issues->with(
                ValidationIssueData::error(
                    code: $this->code(),
                    message: sprintf(
                        'Variable "%s" is defined more than once.',
                        $name,
                    ),
                    path: sprintf(
                        'variables[%d].name',
                        $index,
                    ),
                    metadata: [
                        'variable' => $name,
                        'first_index' => $seen[$name],
                        'duplicate_index' => $index,
                    ],
                ),
            );
        }

        return $issues;
    }
}
