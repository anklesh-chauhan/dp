<?php

declare(strict_types=1);

namespace App\Domain\DocumentTemplate\AI\Rules;

use App\Foundation\AI\Validation\Collections\ValidationIssueCollection;
use App\Foundation\AI\Validation\Contracts\ValidationRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationIssueData;

final readonly class ValidSectionStructureRule implements ValidationRule
{
    public const string CODE = 'valid_section_structure';

    public function code(): string
    {
        return self::CODE;
    }

    public function validate(
        mixed $artifact,
        ValidationContext $context,
    ): ValidationIssueCollection {
        if (! is_array($artifact)) {
            return $this->issue(
                'AI template generation returned invalid sections.',
                'sections',
            );
        }

        $sections = $artifact['sections'] ?? null;

        if (! is_array($sections)) {
            return $this->issue(
                'AI template generation returned invalid sections.',
                'sections',
            );
        }

        $issues = new ValidationIssueCollection;

        foreach ($sections as $index => $section) {
            if (! is_array($section)) {
                $issues = $issues->with(ValidationIssueData::error(
                    code: $this->code(),
                    message: 'AI template generation returned an invalid section.',
                    path: "sections[{$index}]",
                ));

                continue;
            }

            foreach (['title', 'content', 'section_order', 'section_type'] as $key) {
                if (array_key_exists($key, $section)) {
                    continue;
                }

                $issues = $issues->with(ValidationIssueData::error(
                    code: $this->code(),
                    message: "AI template section is missing [{$key}].",
                    path: "sections[{$index}].{$key}",
                ));
            }

            if (
                ! isset($section['title'])
                || ! is_string($section['title'])
                || trim($section['title']) === ''
                || ! array_key_exists('content', $section)
                || ! is_string($section['content'])
                || ! isset($section['section_order'])
                || ! is_int($section['section_order'])
                || ! isset($section['section_type'])
                || ! is_string($section['section_type'])
                || trim($section['section_type']) === ''
            ) {
                $issues = $issues->with(ValidationIssueData::error(
                    code: $this->code(),
                    message: 'AI template generation returned invalid section data.',
                    path: "sections[{$index}]",
                ));
            }
        }

        return $issues;
    }

    private function issue(
        string $message,
        string $path,
    ): ValidationIssueCollection {
        return new ValidationIssueCollection([
            ValidationIssueData::error(
                code: $this->code(),
                message: $message,
                path: $path,
            ),
        ]);
    }
}
