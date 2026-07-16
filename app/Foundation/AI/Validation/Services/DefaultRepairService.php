<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Services;

use App\Foundation\AI\Validation\Contracts\RepairPipeline;
use App\Foundation\AI\Validation\Contracts\RepairService;
use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

final readonly class DefaultRepairService implements RepairService
{
    public function __construct(
        private RepairPipeline $pipeline,
    ) {
    }

    public function repair(
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): RepairResult {
        return $this->pipeline->repair(
            artifact: $artifact,
            validationResult: $validationResult,
            context: $context,
        );
    }
}
