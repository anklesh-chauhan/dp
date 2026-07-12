<?php

declare(strict_types=1);

namespace Tests\Support\AI;

use App\Services\AI\Contracts\LLMProvider;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\LLMCapability;
use RuntimeException;

final class FakeLLMProvider implements LLMProvider
{
    /**
     * @var array<int, LLMCapability>
     */
    private array $capabilities;

    /**
     * @var array<int, LLMResponse>
     */
    private array $responses = [];

    private ?RuntimeException $exception = null;

    public int $callCount = 0;

    /**
     * @param array<int, LLMCapability> $capabilities
     */
    public function __construct(
        private readonly string $providerName,
        private readonly string $modelName = 'fake-model',
        array $capabilities = [
            LLMCapability::TEXT_GENERATION,
            LLMCapability::STRUCTURED_OUTPUT,
        ],
    ) {
        $this->capabilities = $capabilities;
    }

    public function name(): string
    {
        return $this->providerName;
    }

    public function model(): string
    {
        return $this->modelName;
    }

    public function supports(LLMCapability $capability): bool
    {
        return in_array(
            $capability,
            $this->capabilities,
            true,
        );
    }

    public function generate(LLMRequest $request): LLMResponse
    {
        $this->callCount++;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        $response = array_shift($this->responses);

        if (! $response instanceof LLMResponse) {
            throw new RuntimeException(
                sprintf(
                    'Fake provider [%s] has no configured response for call [%d].',
                    $this->providerName,
                    $this->callCount,
                ),
            );
        }

        return $response;
    }

    public function willReturn(LLMResponse $response): self
    {
        $this->responses = [$response];
        $this->exception = null;

        return $this;
    }

    /**
     * @param array<int, LLMResponse> $responses
     */
    public function willReturnSequence(array $responses): self
    {
        $this->responses = array_values($responses);
        $this->exception = null;

        return $this;
    }

    public function willFail(
        string $message = 'Fake provider failure.',
    ): self {
        $this->exception = new RuntimeException($message);
        $this->responses = [];

        return $this;
    }
}
