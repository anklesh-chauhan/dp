<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\ValueObjects;

final readonly class RepairResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private mixed $artifact,
        private bool $successful,
        private bool $modified,
        private array $metadata = [],
    ) {
    }

    public function artifact(): mixed
    {
        return $this->artifact;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
