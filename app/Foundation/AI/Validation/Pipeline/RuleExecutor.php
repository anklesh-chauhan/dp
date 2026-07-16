<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Pipeline;

use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\Exceptions\RuleExecutionException;
use Throwable;

final readonly class RuleExecutor
{
    /**
     * Execute a single validation rule.
     *
     * @throws RuleExecutionException
     */
    public function execute(
        ValidationRule $rule,
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection
    {
        try {
            return $rule->validate(
                $artifact,
                $context,
            );
        } catch (RuleExecutionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RuleExecutionException::forRule(
                $rule,
                $exception,
            );
        }
    }
}
