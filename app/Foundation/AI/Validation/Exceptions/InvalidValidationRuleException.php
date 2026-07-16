<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Exceptions;

final class InvalidValidationRuleException extends ValidationException
{
    public static function because(
        string $reason,
    ): self {
        return new self(
            sprintf(
                'Invalid validation rule: %s',
                $reason,
            ),
        );
    }
}
