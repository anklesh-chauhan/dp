<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use InvalidArgumentException;

final readonly class ArraySizeRule implements ValidationRule
{
    public const CODE = 'array-size';

    public function __construct(
        private ArtifactAccessor $accessor,
        private string $field,
        private ?int $minimumCount = null,
        private ?int $maximumCount = null,
    ) {
        if ($this->minimumCount !== null && $this->minimumCount < 0) {
            throw new InvalidArgumentException(
                'Minimum count cannot be negative.',
            );
        }

        if ($this->maximumCount !== null && $this->maximumCount < 0) {
            throw new InvalidArgumentException(
                'Maximum count cannot be negative.',
            );
        }

        if (
            $this->minimumCount !== null &&
            $this->maximumCount !== null &&
            $this->minimumCount > $this->maximumCount
        ) {
            throw new InvalidArgumentException(
                'Minimum count cannot exceed maximum count.',
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

        if (! is_array($value)) {
            return $issues;
        }

        $count = count($value);

        if (
            $this->minimumCount !== null &&
            $count < $this->minimumCount
        ) {
            $issues = $issues->with(
                ValidationIssueData::error(
                    code: 'array_too_small',
                    message: sprintf(
                        'Field "%s" must contain at least %d item(s).',
                        $this->field,
                        $this->minimumCount,
                    ),
                    path: $this->field,
                ),
            );
        }

        if (
            $this->maximumCount !== null &&
            $count > $this->maximumCount
        ) {
            $issues = $issues->with(
                ValidationIssueData::error(
                    code: 'array_too_large',
                    message: sprintf(
                        'Field "%s" must not contain more than %d item(s).',
                        $this->field,
                        $this->maximumCount,
                    ),
                    path: $this->field,
                ),
            );
        }

        return $issues;
    }
}
