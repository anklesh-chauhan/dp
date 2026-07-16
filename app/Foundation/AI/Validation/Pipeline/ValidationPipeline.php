<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Pipeline;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Collections\ValidationRuleCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

final readonly class ValidationPipeline
{
    public function __construct(
        private RuleExecutor $executor,
    ) {
    }

    /**
     * Executes all validation rules.
     */
    public function execute(
        mixed $artifact,
        ValidationContext $context,
        ValidationRuleCollection $rules,
    ): ValidationIssueCollection {
        $issues = [];

        foreach ($rules as $rule) {
            $result = $this->executor->execute(
                $rule,
                $artifact,
                $context,
            );

            foreach ($result as $issue) {
                $issues[] = $issue;
            }
        }

        return new ValidationIssueCollection($issues);
    }
}
