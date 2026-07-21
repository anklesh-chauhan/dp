<?php

declare(strict_types=1);

namespace App\Foundation\AI\Integrity;

use App\Foundation\AI\Contracts\IntegrityEngine;
use App\Foundation\AI\Contracts\RepairPolicy;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\Contracts\RepairService;
use App\Foundation\AI\Validation\Contracts\ValidationEngine;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

final readonly class DefaultIntegrityEngine implements IntegrityEngine
{
    public function __construct(
        private ValidationEngine $validationEngine,
        private RepairService $repairService,
        private RepairPolicy $repairPolicy,
    ) {
    }

    public function process(
        mixed $artifact,
        ValidationContext $context,
        ValidationRuleCollection $rules,
    ): IntegrityResult {
        $initialValidation = $this->validationEngine->validate(
            artifact: $artifact,
            context: $context,
            rules: $rules,
        );

        if ($initialValidation->passed()) {
            return new IntegrityResult(
                initialValidation: $initialValidation,
            );
        }

        if (! $this->repairPolicy->shouldRepair(
            artifact: $artifact,
            result: $initialValidation,
            context: $context,
        )) {
            return new IntegrityResult(
                initialValidation: $initialValidation,
            );
        }

        $repairResult = $this->repairService->repair(
            artifact: $artifact,
            validationResult: $initialValidation,
            context: $context,
        );

        $repairedArtifact = $repairResult->artifact();

        $finalValidation = $this->validationEngine->validate(
            artifact: $repairedArtifact,
            context: $context,
            rules: $rules,
        );

        return new IntegrityResult(
            initialValidation: $initialValidation,
            repairResult: $repairResult,
            finalValidation: $finalValidation,
        );
    }
}
