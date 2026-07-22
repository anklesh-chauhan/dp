<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use InvalidArgumentException;

final readonly class StringLengthRule implements ValidationRule
{
    public const CODE = 'string-length';

    public function __construct(
        private ArtifactAccessor $accessor,
        private string $field,
        private ?int $minimumLength = null,
        private ?int $maximumLength = null,
    ) {
        if ($this->minimumLength !== null && $this->minimumLength < 0) {
            throw new InvalidArgumentException(
                'Minimum length cannot be negative.',
            );
        }

        if ($this->maximumLength !== null && $this->maximumLength < 0) {
            throw new InvalidArgumentException(
                'Maximum length cannot be negative.',
            );
        }

        if (
            $this->minimumLength !== null &&
            $this->maximumLength !== null &&
            $this->minimumLength > $this->maximumLength
        ) {
            throw new InvalidArgumentException(
                'Minimum length cannot exceed maximum length.',
            );
        }
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

        $value = $this->accessor->get(
            artifact: $artifact,
            path: $this->field,
        );

        if (! is_string($value)) {
            return $issues;
        }

        $length = mb_strlen($value, 'UTF-8');

        if (
            $this->minimumLength !== null &&
            $length < $this->minimumLength
        ) {
            $issues = $issues->with(
                ValidationIssueData::error(
                    code: 'string_too_short',
                    message: sprintf(
                        'Field "%s" must contain at least %d characters.',
                        $this->field,
                        $this->minimumLength,
                    ),
                    path: $this->field,
                ),
            );
        }

        if (
            $this->maximumLength !== null &&
            $length > $this->maximumLength
        ) {
            $issues = $issues->with(
                ValidationIssueData::error(
                    code: 'string_too_long',
                    message: sprintf(
                        'Field "%s" must not exceed %d characters.',
                        $this->field,
                        $this->maximumLength,
                    ),
                    path: $this->field,
                ),
            );
        }

        return $issues;
    }
}
