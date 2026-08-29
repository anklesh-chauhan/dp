<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Validation;

use Anklesh\AiPlatform\Contracts\OutputRule;
use Anklesh\AiPlatform\Data\ValidationIssue;
use Anklesh\AiPlatform\Data\ValidationResult;
use InvalidArgumentException;

final class OutputValidator
{
    /**
     * @param  array<string, mixed>|string  $output
     * @param  iterable<OutputRule>  $rules
     */
    public function validate(array|string $output, iterable $rules): ValidationResult
    {
        $issues = [];

        foreach ($rules as $rule) {
            if (! $rule instanceof OutputRule) {
                throw new InvalidArgumentException(sprintf(
                    'AI output rules must implement [%s].',
                    OutputRule::class,
                ));
            }

            foreach ($rule->validate($output) as $issue) {
                if (! $issue instanceof ValidationIssue) {
                    throw new InvalidArgumentException(sprintf(
                        'AI output rules must return instances of [%s].',
                        ValidationIssue::class,
                    ));
                }

                $issues[] = $issue;
            }
        }

        return new ValidationResult($issues);
    }
}
