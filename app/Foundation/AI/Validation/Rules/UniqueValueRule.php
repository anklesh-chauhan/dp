<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class UniqueValueRule implements ValidationRule
{
    public const CODE = 'unique-value';

    public function __construct(
        private ArtifactAccessor $accessor,
        private string $collectionField,
        private string $valueField,
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

        $collection = $this->accessor->get(
            artifact: $artifact,
            path: $this->collectionField,
        );

        if (! is_array($collection)) {
            return $issues;
        }

        /** @var array<string, bool> $seen */
        $seen = [];

        foreach ($collection as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! $this->accessor->exists($item, $this->valueField)) {
                continue;
            }

            $value = $this->accessor->get(
                artifact: $item,
                path: $this->valueField,
            );

            if (! is_scalar($value)) {
                continue;
            }

            $key = serialize($value);

            if (isset($seen[$key])) {
                $issues = $issues->with(
                    ValidationIssueData::error(
                        code: 'duplicate_value',
                        message: sprintf(
                            'Duplicate value "%s" found for "%s".',
                            (string) $value,
                            $this->valueField,
                        ),
                        path: $this->collectionField,
                    ),
                );

                continue;
            }

            $seen[$key] = true;
        }

        return $issues;
    }
}
