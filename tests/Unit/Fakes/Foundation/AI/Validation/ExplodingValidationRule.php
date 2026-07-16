<?php

declare(strict_types=1);

namespace Tests\Unit\Fakes\Foundation\AI\Validation;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use RuntimeException;

final readonly class ExplodingValidationRule implements ValidationRule
{
    public function code(): string
    {
        return 'exploding-rule';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        throw new RuntimeException('Boom!');
    }
}
