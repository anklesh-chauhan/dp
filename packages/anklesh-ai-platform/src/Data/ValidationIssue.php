<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Data;

use Anklesh\AiPlatform\Enums\ValidationSeverity;

final readonly class ValidationIssue
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $path = null,
        public ValidationSeverity $severity = ValidationSeverity::Error,
    ) {}
}
