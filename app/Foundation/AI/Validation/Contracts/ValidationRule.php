<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Contracts;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

/**
 * Defines a single validation rule.
 *
 * Implementations should be stateless and reusable.
 */
interface ValidationRule
{
    /**
     * Returns the unique machine-readable identifier of the rule.
     *
     * Examples:
     *  - required-title
     *  - valid-json
     *  - duplicate-variable
     */
    public function code(): string;

    /**
     * Validates the supplied artifact.
     *
     * @param mixed $artifact The artifact to validate.
     */
    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection;
}
