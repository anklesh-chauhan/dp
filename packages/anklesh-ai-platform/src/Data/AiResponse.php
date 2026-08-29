<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Data;

use RuntimeException;

final readonly class AiResponse
{
    /**
     * @param  array<string, mixed>|string  $content
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array|string $content,
        public string $provider,
        public ?string $model = null,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public array $metadata = [],
    ) {}

    public function text(): string
    {
        if (! is_string($this->content)) {
            throw new RuntimeException('The AI response contains structured output, not text.');
        }

        return $this->content;
    }

    /** @return array<string, mixed> */
    public function structured(): array
    {
        if (! is_array($this->content)) {
            throw new RuntimeException('The AI response contains text, not structured output.');
        }

        return $this->content;
    }
}
