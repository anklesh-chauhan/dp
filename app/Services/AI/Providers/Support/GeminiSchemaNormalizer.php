<?php

declare(strict_types=1);

namespace App\Services\AI\Providers\Support;

final class GeminiSchemaNormalizer
{
    /**
     * @var array<int, string>
     */
    private const UNSUPPORTED_KEYWORDS = [
        'additionalProperties',
        '$schema',
        '$id',
        '$ref',
        '$defs',
        'definitions',
    ];

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    public function normalize(array $schema): array
    {
        return $this->normalizeNode($schema);
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>
     */
    private function normalizeNode(array $node): array
    {
        foreach (self::UNSUPPORTED_KEYWORDS as $keyword) {
            unset($node[$keyword]);
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (array_is_list($value)) {
                $node[$key] = array_map(
                    fn (mixed $item): mixed => is_array($item)
                        ? $this->normalizeNode($item)
                        : $item,
                    $value,
                );

                continue;
            }

            $node[$key] = $this->normalizeNode($value);
        }

        return $node;
    }
}
