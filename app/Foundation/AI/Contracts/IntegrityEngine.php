<?php

declare(strict_types=1);

namespace App\Foundation\AI\Contracts;

use App\Foundation\AI\Integrity\IntegrityResult;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

interface IntegrityEngine
{
    /**
     * Execute the complete integrity workflow.
     *
     * Workflow:
     * 1. Validate the artifact.
     * 2. Optionally repair it.
     * 3. Revalidate after repair.
     *
     * @param mixed $artifact The artifact to process.
     * @param ValidationContext $context The context for the artifact.
     * @param ValidationRuleCollection $rules The rules to use for validation.
     *
     * @return IntegrityResult The result of the integrity workflow.
     */
    public function process(
        mixed $artifact,
        ValidationContext $context,
        ValidationRuleCollection $rules,
    ): IntegrityResult;
}
