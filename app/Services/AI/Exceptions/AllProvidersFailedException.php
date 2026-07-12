<?php

declare(strict_types=1);

namespace App\Services\AI\Exceptions;

use RuntimeException;

final class AllProvidersFailedException extends RuntimeException
{
    public static function fromFailures(array $failures): self
    {
        return new self(
            'All eligible LLM providers failed: '.
            json_encode(
                $failures,
                JSON_THROW_ON_ERROR,
            ),
        );
    }
}
