<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when invalid validation rule metadata is supplied.
 */
final class InvalidRuleMetadataException extends InvalidArgumentException
{
    public static function emptyCode(): self
    {
        return new self('Rule code cannot be empty.');
    }

    public static function emptyName(): self
    {
        return new self('Rule name cannot be empty.');
    }

    public static function emptyVersion(): self
    {
        return new self('Rule version cannot be empty.');
    }
}
