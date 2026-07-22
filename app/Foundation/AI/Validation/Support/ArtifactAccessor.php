<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Support;

final readonly class ArtifactAccessor
{
    public function exists(
        mixed $artifact,
        string $path,
    ): bool {
        return $this->resolve($artifact, $path)['exists'];
    }

    public function has(
        mixed $artifact,
        string $path,
    ): bool {
        $resolved = $this->resolve($artifact, $path);

        return $resolved['exists']
            && $resolved['value'] !== null;
    }

    public function get(
        mixed $artifact,
        string $path,
        mixed $default = null,
    ): mixed {
        $resolved = $this->resolve($artifact, $path);

        return $resolved['exists']
            ? $resolved['value']
            : $default;
    }

    /**
     * @return array{exists: bool, value: mixed}
     */
    private function resolve(
        mixed $artifact,
        string $path,
    ): array {
        if (! is_array($artifact)) {
            return [
                'exists' => false,
                'value' => null,
            ];
        }

        if ($path === '') {
            return [
                'exists' => true,
                'value' => $artifact,
            ];
        }

        $current = $artifact;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($current)) {
                return [
                    'exists' => false,
                    'value' => null,
                ];
            }

            if (! array_key_exists($segment, $current)) {
                return [
                    'exists' => false,
                    'value' => null,
                ];
            }

            $current = $current[$segment];
        }

        return [
            'exists' => true,
            'value' => $current,
        ];
    }
}
