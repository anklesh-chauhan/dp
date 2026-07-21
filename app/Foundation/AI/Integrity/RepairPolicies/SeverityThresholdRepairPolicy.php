<?php

declare(strict_types=1);

namespace App\Foundation\AI\Integrity\RepairPolicies;

use App\Foundation\AI\Contracts\RepairPolicy;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

final readonly class SeverityThresholdRepairPolicy implements RepairPolicy
{
    public function __construct(
        private ValidationSeverity $threshold,
    ) {
    }

    /**
     * Repairs when the highest validation severity
     * meets or exceeds the configured threshold.
     */
    public function shouldRepair(
        mixed $artifact,
        ValidationResult $result,
        ValidationContext $context,
    ): bool {
        $highestSeverity = $result->highestSeverity();

        if ($highestSeverity === null) {
            return false;
        }

        return $highestSeverity->priority() >= $this->threshold->priority();
    }
}
