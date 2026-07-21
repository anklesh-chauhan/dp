<?php

declare(strict_types=1);

namespace App\Foundation\AI\Integrity\RepairPolicies;

use App\Foundation\AI\Contracts\RepairPolicy;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

final readonly class ErrorOnlyRepairPolicy implements RepairPolicy
{
    /**
     * Repair only when blocking validation issues exist.
     */
    public function shouldRepair(
        mixed $artifact,
        ValidationResult $result,
        ValidationContext $context,
    ): bool {
        return $result->hasErrors()
            || $result->hasCriticalIssues();
    }
}
