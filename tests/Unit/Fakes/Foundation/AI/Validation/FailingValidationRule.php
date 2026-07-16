<?php

declare(strict_types=1);

namespace Tests\Unit\Fakes\Foundation\AI\Validation;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class FailingValidationRule implements ValidationRule
{
    public function code(): string
    {
        return 'failing-rule';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        return new ValidationIssueCollection([
            ValidationIssueData::error(
                code: 'missing-title',
                message: 'Title is required.',
            ),
        ]);
    }
}
