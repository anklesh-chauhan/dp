<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\ValueObjects;

/**
 * Provides contextual information to validation rules.
 *
 * The context is immutable and may contain metadata required by
 * validators without coupling them to specific application services.
 */
final readonly class ValidationContext
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private string $artifactType,
        private array $attributes = [],
    ) {
    }

    /**
     * Returns the logical artifact type.
     *
     * Examples:
     *  - sop_template
     *  - deviation
     *  - risk_assessment
     */
    public function artifactType(): string
    {
        return $this->artifactType;
    }

    /**
     * Returns all context attributes.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Determines whether an attribute exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Returns a context attribute.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->attributes)
            ? $this->attributes[$key]
            : $default;
    }
}
