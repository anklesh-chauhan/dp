<?php

declare(strict_types=1);

namespace App\Foundation\AI\Integrity;

use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

/**
 * Represents the complete integrity workflow outcome.
 *
 * Contains the initial validation result,
 * optional repair result,
 * and optional post-repair validation.
 */

final readonly class IntegrityResult
{
    public function __construct(
        private ValidationResult $initialValidation,
        private ?RepairResult $repairResult = null,
        private ?ValidationResult $finalValidation = null,
    ) {
    }

    public function initialValidation(): ValidationResult
    {
        return $this->initialValidation;
    }

    public function repairResult(): ?RepairResult
    {
        return $this->repairResult;
    }

    public function finalValidation(): ?ValidationResult
    {
        return $this->finalValidation;
    }

    public function repairAttempted(): bool
    {
        return $this->repairResult !== null;
    }

    public function wasRepaired(): bool
    {
        return $this->repairResult?->wasModified() ?? false;
    }

    public function repairSucceeded(): bool
    {
        return $this->repairResult?->isSuccessful() ?? false;
    }

    public function isValid(): bool
    {
        return $this->finalValidation?->passed()
            ?? $this->initialValidation->passed();
    }

    public function wasRevalidated(): bool
    {
        return $this->finalValidation !== null;
    }
}
