<?php

declare(strict_types=1);

namespace App\Services\AI\Data;

use RuntimeException;

final readonly class LLMResponse
{
    public function __construct(
        public mixed $content,
        public string $provider,
        public string $model,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $durationMs = null,
        public array $metadata = [],
    ) {}

    public function structured(): array
    {
        if (! is_array($this->content)) {
            throw new RuntimeException(
                'The LLM response does not contain structured content.',
            );
        }

        return $this->content;
    }

    public function text(): string
    {
        if (! is_string($this->content)) {
            throw new RuntimeException(
                'The LLM response does not contain text content.',
            );
        }

        return $this->content;
    }
}
