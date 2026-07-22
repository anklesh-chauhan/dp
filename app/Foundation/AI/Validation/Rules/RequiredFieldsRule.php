<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class RequiredFieldsRule implements ValidationRule
{
    /**
     * @param list<string> $requiredFields
     */
    public function __construct(
        private ArtifactAccessor $accessor,
        private array $requiredFields,
    ) {
    }

    public function code(): string
    {
        return 'required-fields';
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        $issues = new ValidationIssueCollection();

        foreach ($this->requiredFields as $field) {
            if ($this->accessor->exists($artifact, $field)) {
                continue;
            }

            $issues = $issues->with(
                ValidationIssueData::error(
                    code: 'required_field_missing',
                    message: sprintf(
                        'Required field "%s" is missing.',
                        $field,
                    ),
                    path: $field,
                ),
            );
        }

        return $issues;
    }
}
