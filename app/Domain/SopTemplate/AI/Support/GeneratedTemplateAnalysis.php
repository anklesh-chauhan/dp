<?php

declare(strict_types=1);

namespace App\Domain\SopTemplate\AI\Support;

use InvalidArgumentException;

final readonly class GeneratedTemplateAnalysis
{
    /**
     * @param array<int, array<string, mixed>> $sections
     * @param array<int, array<string, mixed>> $variables
     * @param array<int, string> $variableNames
     * @param array<int, string> $placeholderNames
     */
    private function __construct(
        private array $sections,
        private array $variables,
        private array $variableNames,
        private array $placeholderNames,
    ) {
    }

    /**
     * Build an analysis from a generated template.
     *
     * @param array<string, mixed> $generatedTemplate
     */
    public static function analyze(
        array $generatedTemplate,
        PlaceholderExtractor $placeholderExtractor,
    ): self {
        $sections = self::extractSections($generatedTemplate);

        $variables = self::extractVariables($generatedTemplate);

        $variableNames = self::extractVariableNames($variables);

        $placeholderNames = self::extractPlaceholderNames(
            sections: $sections,
            placeholderExtractor: $placeholderExtractor,
        );

        return new self(
            sections: $sections,
            variables: $variables,
            variableNames: $variableNames,
            placeholderNames: $placeholderNames,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function variables(): array
    {
        return $this->variables;
    }

    /**
     * @return array<int, string>
     */
    public function variableNames(): array
    {
        return $this->variableNames;
    }

    /**
     * @return array<int, string>
     */
    public function placeholderNames(): array
    {
        return $this->placeholderNames;
    }

    /**
     * @param array<string, mixed> $generatedTemplate
     *
     * @return array<int, array<string, mixed>>
     */
    private static function extractSections(array $generatedTemplate): array
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
    private static function extractVariables(array $generatedTemplate): array
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
    private static function extractVariableNames(array $variables): array
    {
        $names = [];

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

            $names[] = $name;
        }

        return $names;
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     *
     * @return array<int, string>
     */
    private static function extractPlaceholderNames(
        array $sections,
        PlaceholderExtractor $placeholderExtractor,
    ): array {
        $placeholders = [];

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

            $placeholders = [
                ...$placeholders,
                ...$placeholderExtractor->extract($content),
            ];
        }

        return array_values(
            array_unique($placeholders),
        );
    }
}
