<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InvalidSignedLicenseException extends RuntimeException
{
    public static function signature(): self
    {
        return new self('The product license signature is invalid.');
    }

    public static function payload(string $reason): self
    {
        return new self("The product license payload is invalid: {$reason}");
    }
}
