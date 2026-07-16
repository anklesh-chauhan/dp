<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\ValueObjects;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;

/**
 * Represents the outcome of a validation operation.
 */
final readonly class ValidationResult
{
    public function __construct(
        private ValidationIssueCollection $issues = new ValidationIssueCollection(),
    ) {
    }

    /**
     * Returns all validation issues.
     */
    public function issues(): ValidationIssueCollection
    {
        return $this->issues;
    }

    /**
     * Determines whether validation passed.
     */
    public function passed(): bool
    {
        return ! $this->failed();
    }

    /**
     * Determines whether validation failed.
     */
    public function failed(): bool
    {
        return $this->issues->containsBlockingIssues();
    }

    /**
     * Returns true when one or more warnings exist.
     */
    public function hasWarnings(): bool
    {
        return $this->issues->hasSeverity(ValidationSeverity::WARNING);
    }

    /**
     * Returns true when one or more errors exist.
     */
    public function hasErrors(): bool
    {
        return $this->issues->hasSeverity(ValidationSeverity::ERROR);
    }

    /**
     * Returns true when one or more critical issues exist.
     */
    public function hasCriticalIssues(): bool
    {
        return $this->issues->hasSeverity(ValidationSeverity::CRITICAL);
    }

    /**
     * Returns the highest severity encountered.
     */
    public function highestSeverity(): ?ValidationSeverity
    {
        return $this->issues->highestSeverity();
    }
}
