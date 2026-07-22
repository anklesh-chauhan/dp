<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use InvalidArgumentException;

final readonly class RegexRule implements ValidationRule
{
    public const CODE = 'regex';

    public function __construct(
        private ArtifactAccessor $accessor,
        private string $field,
        private string $pattern,
    ) {
        $this->assertValidPattern($pattern);
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

        if (preg_match($this->pattern, $value) === 1) {
            return $issues;
        }

        return $issues->with(
            ValidationIssueData::error(
                code: 'regex_mismatch',
                message: sprintf(
                    'Field "%s" does not match the required format.',
                    $this->field,
                ),
                path: $this->field,
            ),
        );
    }

    private function assertValidPattern(string $pattern): void
    {
        set_error_handler(static fn (): bool => true);

        try {
            $result = preg_match($pattern, '');
        } finally {
            restore_error_handler();
        }

        if ($result === false) {
            throw new InvalidArgumentException(
                'The regular expression pattern is invalid.',
            );
        }
    }
}
