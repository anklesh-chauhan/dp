<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;
use Closure;

final readonly class CrossFieldRule implements ValidationRule
{
    public const CODE = 'cross-field';

    private Closure $predicate;

    /**
     * @param callable(mixed, ValidationContext): bool $predicate
     */
    public function __construct(
        private string $issueCode,
        private string $message,
        callable $predicate,
        private ?string $path = null,
    ) {
        $this->predicate = $predicate instanceof Closure
            ? $predicate
            : Closure::fromCallable($predicate);
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

        $passes = ($this->predicate)($artifact, $context);

        if ($passes) {
            return $issues;
        }

        return $issues->with(
            ValidationIssueData::error(
                code: $this->issueCode,
                message: $this->message,
                path: $this->path,
            ),
        );
    }
}
