<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use InvalidArgumentException;
use Traversable;

final readonly class EnumValueRule implements ValidationRule
{
    public const CODE = 'enum-value';

    /**
     * @var array<int, scalar|null>
     */
    private array $allowedValues;

    /**
     * @param iterable<scalar|null> $allowedValues
     */
    public function __construct(
        private ArtifactAccessor $accessor,
        private string $field,
        iterable $allowedValues,
    ) {
        $this->allowedValues = is_array($allowedValues)
            ? array_values($allowedValues)
            : array_values(iterator_to_array($allowedValues, false));

        if ($this->allowedValues === []) {
            throw new InvalidArgumentException(
                'Allowed values cannot be empty.',
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

        if ($value === null) {
            return $issues;
        }

        if (! in_array($value, $this->allowedValues, true)) {
            return $issues->with(
                ValidationIssueData::error(
                    code: 'invalid_enum_value',
                    message: sprintf(
                        'Field "%s" must be one of: %s.',
                        $this->field,
                        implode(', ', array_map(
                            static fn (mixed $value): string => var_export($value, true),
                            $this->allowedValues,
                        )),
                    ),
                    path: $this->field,
                ),
            );
        }

        return $issues;
    }
}
