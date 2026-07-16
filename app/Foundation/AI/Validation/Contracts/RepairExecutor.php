<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Contracts;

use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

interface RepairExecutor
{
    /**
     * Executes a repair strategy.
     *
     * @param mixed $artifact
     */
    public function execute(
        RepairStrategy $strategy,
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): RepairResult;
}
