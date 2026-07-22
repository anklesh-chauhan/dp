<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class EmptyValueRule implements ValidationRule
{
    public const CODE = 'empty-value';

    public function __construct(
        private ArtifactAccessor $accessor,
        private string $field,
    ) {
    }

    public function code(): string
    {
        return self::CODE;
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        $issues = new ValidationIssueCollection();

        if (! $this->accessor->exists($artifact, $this->field)) {
            return $issues;
        }

        $value = $this->accessor->get(
            artifact: $artifact,
            path: $this->field,
        );

        if (! $this->isEmptyValue($value)) {
            return $issues;
        }

        return $issues->with(
            ValidationIssueData::error(
                code: 'empty_value',
                message: sprintf(
                    'Field "%s" must not be empty.',
                    $this->field,
                ),
                path: $this->field,
            ),
        );
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }
}
