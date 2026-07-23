<?php

declare(strict_types=1);

namespace App\Domain\SopTemplate\AI\Support;

final readonly class PlaceholderExtractor
{
    private const string PLACEHOLDER_PATTERN = '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/';

    /**
     * Extract all unique placeholder names from content.
     *
     * @return array<int, string>
     */
    public function extract(string $content): array
    {
        preg_match_all(
            self::PLACEHOLDER_PATTERN,
            $content,
            $matches,
        );

        /** @var array<int, string> $placeholders */
        $placeholders = $matches[1] ?? [];

        return array_values(
            array_unique($placeholders),
        );
    }
}
