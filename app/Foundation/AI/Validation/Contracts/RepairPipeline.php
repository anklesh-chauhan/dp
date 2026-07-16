<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Contracts;

use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

interface RepairPipeline
{
    /**
     * @param mixed $artifact
     */
    public function repair(
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): RepairResult;
}
