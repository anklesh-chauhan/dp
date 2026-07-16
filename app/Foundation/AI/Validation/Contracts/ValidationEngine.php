<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Contracts;

use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

/**
 * Executes a collection of validation rules against an artifact.
 */
interface ValidationEngine
{
    /**
     * Validates the supplied artifact.
     *
     * @param mixed $artifact The artifact to validate.
     */
    public function validate(
        mixed $artifact,
        ValidationContext $context,
        ValidationRuleCollection $rules,
    ): ValidationResult;
}
