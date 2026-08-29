<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Data;

use Anklesh\AiPlatform\Enums\ValidationSeverity;

final readonly class ValidationResult
{
    /** @param list<ValidationIssue> $issues */
    public function __construct(public array $issues) {}

    public function passed(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity === ValidationSeverity::Error) {
                return false;
            }
        }

        return true;
    }

    public function failed(): bool
    {
        return ! $this->passed();
    }
}
