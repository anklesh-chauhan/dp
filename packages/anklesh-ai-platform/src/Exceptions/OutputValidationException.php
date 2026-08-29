<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Exceptions;

use Anklesh\AiPlatform\Data\ValidationResult;
use RuntimeException;

final class OutputValidationException extends RuntimeException
{
    public function __construct(public readonly ValidationResult $result)
    {
        parent::__construct('The generated AI output failed deterministic validation.');
    }
}
