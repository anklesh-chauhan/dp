<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Exceptions;

use RuntimeException;

final class NoEligibleProvider extends RuntimeException
{
    public static function for(string $useCase, string $classification): self
    {
        return new self(sprintf(
            'No enabled AI provider is eligible for use case [%s] with data classification [%s].',
            $useCase,
            $classification,
        ));
    }
}
