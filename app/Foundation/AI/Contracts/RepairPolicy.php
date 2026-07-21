<?php

declare(strict_types=1);

namespace App\Foundation\AI\Contracts;

use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

interface RepairPolicy
{
    /**
     * Determine whether the failed validation
     * should trigger an automatic repair attempt.
     *
     * @param mixed $artifact The artifact to repair.
     * @param ValidationResult $result The validation result to check.
     * @param ValidationContext $context The context for the artifact.
     *
     * @return bool Whether to repair the artifact.
     */
    public function shouldRepair(
        mixed $artifact,
        ValidationResult $result,
        ValidationContext $context,
    ): bool;
}
