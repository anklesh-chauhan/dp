<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\AI\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class ValidVariableStructureRule implements ValidationRule
{
    public const string CODE = 'valid_variable_structure';

    public function code(): string
    {
        return self::CODE;
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        if (! is_array($artifact)) {
            return $this->issue(
                'AI template generation returned invalid variables.',
                'variables',
            );
        }

        $variables = $artifact['variables'] ?? null;

        if (! is_array($variables)) {
            return $this->issue(
                'AI template generation returned invalid variables.',
                'variables',
            );
        }

        $issues = new ValidationIssueCollection;

        foreach ($variables as $index => $variable) {
            if (! is_array($variable)) {
                $issues = $issues->with(ValidationIssueData::error(
                    code: $this->code(),
                    message: 'AI template generation returned an invalid variable.',
                    path: "variables[{$index}]",
                ));

                continue;
            }

            foreach (['name', 'label', 'datatype', 'default_value', 'required'] as $key) {
                if (array_key_exists($key, $variable)) {
                    continue;
                }

                $issues = $issues->with(ValidationIssueData::error(
                    code: $this->code(),
                    message: "AI template variable is missing [{$key}].",
                    path: "variables[{$index}].{$key}",
                ));
            }

            if (
                ! isset($variable['name'])
                || ! is_string($variable['name'])
                || trim($variable['name']) === ''
                || ! isset($variable['label'])
                || ! is_string($variable['label'])
                || trim($variable['label']) === ''
                || ! isset($variable['datatype'])
                || ! is_string($variable['datatype'])
                || trim($variable['datatype']) === ''
                || ! array_key_exists('default_value', $variable)
                || ! is_string($variable['default_value'])
                || ! array_key_exists('required', $variable)
                || ! is_bool($variable['required'])
            ) {
                $issues = $issues->with(ValidationIssueData::error(
                    code: $this->code(),
                    message: 'AI template generation returned invalid variable data.',
                    path: "variables[{$index}]",
                ));
            }
        }

        return $issues;
    }

    private function issue(
        string $message,
        string $path,
    ): ValidationIssueCollection {
        return new ValidationIssueCollection([
            ValidationIssueData::error(
                code: $this->code(),
                message: $message,
                path: $path,
            ),
        ]);
    }
}
