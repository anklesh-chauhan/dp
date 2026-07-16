<?php

declare(strict_types=1);

namespace Tests\Unit\Fakes\Foundation\AI\Validation;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

final readonly class PassingValidationRule implements ValidationRule
{
    public function code(): string
    {
        return 'passing-rule';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        return new ValidationIssueCollection();
    }
}
