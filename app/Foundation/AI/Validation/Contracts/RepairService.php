<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Contracts;

use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

/**
 * Repairs an artifact based on validation issues.
 */
interface RepairService
{
    /**
     * Attempts to repair the supplied artifact.
     *
     * @param mixed $artifact The artifact to repair.
     *
     * @return mixed The repaired artifact.
     */
    public function repair(
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): mixed;
}
