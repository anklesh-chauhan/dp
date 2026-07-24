<?php

declare(strict_types=1);

namespace App\Domain\SopTemplate\AI\Support;

final readonly class GeneratedTemplateAnalysis
{
    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<int, array<string, mixed>>  $variables
     * @param  array<int, string>  $variableNames
     * @param  array<int, string>  $placeholderNames
     */
    private function __construct(
        private array $sections,
        private array $variables,
        private array $variableNames,
        private array $placeholderNames,
    ) {}

    /**
     * Build an analysis from a generated template.
     *
     * @param  array<string, mixed>  $generatedTemplate
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
     * @param  array<string, mixed>  $generatedTemplate
     * @return array<int, array<string, mixed>>
     */
    private static function extractSections(array $generatedTemplate): array
    {
        $sections = $generatedTemplate['sections'] ?? null;

        if (! is_array($sections)) {
            return [];
        }

        return array_values(array_filter(
            $sections,
            is_array(...),
        ));
    }

    /**
     * @param  array<string, mixed>  $generatedTemplate
     * @return array<int, array<string, mixed>>
     */
    private static function extractVariables(array $generatedTemplate): array
    {
        $variables = $generatedTemplate['variables'] ?? null;

        if (! is_array($variables)) {
            return [];
        }

        return array_values(array_filter(
            $variables,
            is_array(...),
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     * @return array<int, string>
     */
    private static function extractVariableNames(array $variables): array
    {
        $names = [];

        foreach ($variables as $variable) {
            $name = $variable['name'] ?? null;

            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            $names[] = trim($name);
        }

        return $names;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, string>
     */
    private static function extractPlaceholderNames(
        array $sections,
        PlaceholderExtractor $placeholderExtractor,
    ): array {
        $placeholders = [];

        foreach ($sections as $section) {
            $content = $section['content'] ?? null;

            if (! is_string($content)) {
                continue;
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
