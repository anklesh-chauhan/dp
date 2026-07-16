<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation;

use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\Contracts\ValidationEngine;
use App\Foundation\AI\Validation\Pipeline\ValidationPipeline;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

final readonly class DefaultValidationEngine implements ValidationEngine
{
    public function __construct(
        private ValidationPipeline $pipeline,
    ) {
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
        ValidationRuleCollection $rules,
    ): ValidationResult {
        $issues = $this->pipeline->execute(
            $artifact,
            $context,
            $rules,
        );

        return new ValidationResult($issues);
    }
}
