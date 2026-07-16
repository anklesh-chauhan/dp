<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Contracts;

use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

interface RepairStrategy
{
    /**
     * Determines whether this strategy can attempt a repair.
     *
     * @param mixed $artifact
     */
    public function supports(
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): bool;

    /**
     * Attempts to repair the supplied artifact.
     *
     * @param mixed $artifact
     */
    public function repair(
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): RepairResult;
}
