<?php

declare(strict_types=1);

namespace App\Services\AI\Data;

use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;

final readonly class LLMRequest
{
    public function __construct(
        public string $prompt,
        public AIUseCase $useCase,
        public LLMCapability $capability,
        public AIDataClassification $dataClassification = AIDataClassification::INTERNAL,
        public ?array $jsonSchema = null,
        public ?string $systemPrompt = null,
        public float $temperature = 0.1,
        public ?int $maxTokens = null,
        public array $metadata = [],
    ) {}
}
