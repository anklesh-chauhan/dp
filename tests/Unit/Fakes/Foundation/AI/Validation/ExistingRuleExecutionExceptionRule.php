<?php

declare(strict_types=1);

namespace Tests\Unit\Fakes\Foundation\AI\Validation;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Exceptions\RuleExecutionException;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use RuntimeException;

final readonly class ExistingRuleExecutionExceptionRule implements ValidationRule
{
    public function code(): string
    {
        return 'existing-exception-rule';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        throw RuleExecutionException::forRule(
            $this,
            new RuntimeException('Already wrapped'),
        );
    }
}
