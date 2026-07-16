<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Exceptions;

use App\Foundation\AI\Validation\Contracts\ValidationRule;
use Throwable;

final class RuleExecutionException extends ValidationException
{
    public static function forRule(
        ValidationRule $rule,
        Throwable $previous,
    ): self {
        return new self(
            sprintf(
                'Validation rule "%s" failed during execution.',
                $rule::class,
            ),
            previous: $previous,
        );
    }
}
