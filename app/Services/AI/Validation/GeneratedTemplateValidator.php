<?php

declare(strict_types=1);

namespace App\Services\AI\Validation;

use InvalidArgumentException;
use RuntimeException;

final class GeneratedTemplateValidator
{
    private const string PLACEHOLDER_PATTERN = '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/';

    /**
     * @param array<string, mixed> $generatedTemplate
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $generatedTemplate): void
    {
        $sections = $this->sections($generatedTemplate);
        $variables = $this->variables($generatedTemplate);

        $variableNames = $this->variableNames($variables);
        $placeholderNames = $this->placeholderNames($sections);

        $this->ensureVariableNamesAreValid($variableNames);
        $this->ensureVariableNamesAreUnique($variableNames);

        $this->ensureEveryVariableIsReferenced(
            variableNames: $variableNames,
            placeholderNames: $placeholderNames,
        );

        $this->ensureEveryPlaceholderIsDefined(
            variableNames: $variableNames,
            placeholderNames: $placeholderNames,
        );

        $this->validateSections($sections);
        $this->validateVariables($variables);
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     */
    private function validateSections(array $sections): void
    {
        foreach ($sections as $section) {
            $this->validateSection($section);
        }
    }

    /**
     * @param array<string, mixed> $section
     */
    private function validateSection(array $section): void
    {
        foreach (
            [
                'title',
                'content',
                'section_order',
                'section_type',
            ] as $requiredKey
        ) {
            if (! array_key_exists($requiredKey, $section)) {
                throw new RuntimeException(
                    "AI template section is missing [{$requiredKey}].",
                );
            }
        }

        if (
            ! is_string($section['title'])
            || trim($section['title']) === ''
            || ! is_string($section['content'])
            || ! is_int($section['section_order'])
            || ! is_string($section['section_type'])
            || trim($section['section_type']) === ''
        ) {
            throw new RuntimeException(
                'AI template generation returned invalid section data.',
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $variables
     */
    private function validateVariables(array $variables): void
    {
        foreach ($variables as $variable) {
            $this->validateVariable($variable);
        }
    }

    /**
     * @param array<string, mixed> $variable
     */
    private function validateVariable(array $variable): void
    {
        foreach (
            [
                'name',
                'label',
                'datatype',
                'default_value',
                'required',
            ] as $requiredKey
        ) {
            if (! array_key_exists($requiredKey, $variable)) {
                throw new RuntimeException(
                    "AI template variable is missing [{$requiredKey}].",
                );
            }
        }

        if (
            ! is_string($variable['name'])
            || trim($variable['name']) === ''
            || ! is_string($variable['label'])
            || trim($variable['label']) === ''
            || ! is_string($variable['datatype'])
            || trim($variable['datatype']) === ''
            || ! is_string($variable['default_value'])
            || ! is_bool($variable['required'])
        ) {
            throw new RuntimeException(
                'AI template generation returned invalid variable data.',
            );
        }
    }

    /**
     * @param array<string, mixed> $generatedTemplate
     *
     * @return array<int, array<string, mixed>>
     */
    private function sections(array $generatedTemplate): array
    {
        $sections = $generatedTemplate['sections'] ?? null;

        if (! is_array($sections)) {
            throw new InvalidArgumentException(
                'Generated template sections must be an array.',
            );
        }

        foreach ($sections as $section) {
            if (! is_array($section)) {
                throw new InvalidArgumentException(
                    'Every generated template section must be an array.',
                );
            }
        }

        return array_values($sections);
    }

    /**
     * @param array<string, mixed> $generatedTemplate
     *
     * @return array<int, array<string, mixed>>
     */
    private function variables(array $generatedTemplate): array
    {
        $variables = $generatedTemplate['variables'] ?? null;

        if (! is_array($variables)) {
            throw new InvalidArgumentException(
                'Generated template variables must be an array.',
            );
        }

        foreach ($variables as $variable) {
            if (! is_array($variable)) {
                throw new InvalidArgumentException(
                    'Every generated template variable must be an array.',
                );
            }
        }

        return array_values($variables);
    }

    /**
     * @param array<int, array<string, mixed>> $variables
     *
     * @return array<int, string>
     */
    private function variableNames(array $variables): array
    {
        $variableNames = [];

        foreach ($variables as $index => $variable) {
            if (! array_key_exists('name', $variable)) {
                throw new InvalidArgumentException(
                    "Generated variable at index [{$index}] is missing its name.",
                );
            }

            $name = $variable['name'];

            if (! is_string($name)) {
                throw new InvalidArgumentException(
                    "Generated variable name at index [{$index}] must be a string.",
                );
            }

            $name = trim($name);

            if ($name === '') {
                throw new InvalidArgumentException(
                    "Generated variable name at index [{$index}] must not be empty.",
                );
            }

            $variableNames[] = $name;
        }

        return $variableNames;
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     *
     * @return array<int, string>
     */
    private function placeholderNames(array $sections): array
    {
        $placeholderNames = [];

        foreach ($sections as $index => $section) {
            if (! array_key_exists('content', $section)) {
                throw new InvalidArgumentException(
                    "Generated section at index [{$index}] is missing its content.",
                );
            }

            $content = $section['content'];

            if (! is_string($content)) {
                throw new InvalidArgumentException(
                    "Generated section content at index [{$index}] must be a string.",
                );
            }

            $placeholderNames = [
                ...$placeholderNames,
                ...$this->extractPlaceholders($content),
            ];
        }

        return array_values(
            array_unique($placeholderNames),
        );
    }

    /**
     * @return array<int, string>
     */
    private function extractPlaceholders(string $content): array
    {
        preg_match_all(
            self::PLACEHOLDER_PATTERN,
            $content,
            $matches,
        );

        return array_values(
            array_unique($matches[1] ?? []),
        );
    }

    /**
     * @param array<int, string> $variableNames
     */
    private function ensureVariableNamesAreValid(array $variableNames): void
    {
        foreach ($variableNames as $variableName) {
            if (preg_match('/^[a-z][a-z0-9_]*$/', $variableName) !== 1) {
                throw new InvalidArgumentException(
                    "Generated variable [{$variableName}] must use snake_case.",
                );
            }
        }
    }

    /**
     * @param array<int, string> $variableNames
     */
    private function ensureVariableNamesAreUnique(array $variableNames): void
    {
        $duplicates = collect($variableNames)
            ->duplicates()
            ->unique()
            ->values()
            ->all();

        if ($duplicates === []) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Generated template contains duplicate variables: %s.',
                implode(', ', $duplicates),
            ),
        );
    }

    /**
     * @param array<int, string> $variableNames
     * @param array<int, string> $placeholderNames
     */
    private function ensureEveryVariableIsReferenced(
        array $variableNames,
        array $placeholderNames,
    ): void {
        $orphanVariables = array_values(
            array_diff($variableNames, $placeholderNames),
        );

        if ($orphanVariables === []) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Generated template contains unreferenced variables: %s.',
                implode(', ', $orphanVariables),
            ),
        );
    }

    /**
     * @param array<int, string> $variableNames
     * @param array<int, string> $placeholderNames
     */
    private function ensureEveryPlaceholderIsDefined(
        array $variableNames,
        array $placeholderNames,
    ): void {
        $undefinedPlaceholders = array_values(
            array_diff($placeholderNames, $variableNames),
        );

        if ($undefinedPlaceholders === []) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Generated template contains undefined placeholders: %s.',
                implode(', ', $undefinedPlaceholders),
            ),
        );
    }
}
