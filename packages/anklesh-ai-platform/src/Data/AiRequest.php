<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Data;

use Anklesh\AiPlatform\Contracts\OutputRule;
use Anklesh\AiPlatform\Enums\DataClassification;
use Closure;
use InvalidArgumentException;

final readonly class AiRequest
{
    /**
     * @param  iterable<mixed>  $messages
     * @param  iterable<mixed>  $tools
     * @param  array<int, mixed>  $attachments
     * @param  iterable<OutputRule>  $rules
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $prompt,
        public string $useCase = 'default',
        public DataClassification|string $classification = DataClassification::Internal,
        public string $instructions = '',
        public iterable $messages = [],
        public iterable $tools = [],
        public array $attachments = [],
        public ?Closure $schema = null,
        public iterable $rules = [],
        public ?int $timeout = null,
        public array $metadata = [],
    ) {
        if (trim($this->prompt) === '') {
            throw new InvalidArgumentException('The AI prompt may not be empty.');
        }

        if (trim($this->useCase) === '') {
            throw new InvalidArgumentException('The AI use case may not be empty.');
        }

        if ($this->timeout !== null && $this->timeout < 1) {
            throw new InvalidArgumentException('The AI timeout must be at least one second.');
        }
    }

    public function classificationValue(): string
    {
        return $this->classification instanceof DataClassification
            ? $this->classification->value
            : mb_strtolower(trim($this->classification));
    }
}
