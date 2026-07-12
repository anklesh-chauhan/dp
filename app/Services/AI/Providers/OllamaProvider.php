<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\LLMProvider;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\LLMCapability;
use Illuminate\Http\Client\Factory as HttpFactory;
use JsonException;
use RuntimeException;

final readonly class OllamaProvider implements LLMProvider
{
    public function __construct(
        private HttpFactory $http,
    ) {}

    public function name(): string
    {
        return 'ollama';
    }

    public function model(): string
    {
        return (string) config('ai.providers.ollama.model');
    }

    public function supports(LLMCapability $capability): bool
    {
        return in_array($capability, [
            LLMCapability::TEXT_GENERATION,
            LLMCapability::STRUCTURED_OUTPUT,
        ], true);
    }

    /**
     * @throws JsonException
     */
    public function generate(LLMRequest $request): LLMResponse
    {
        $startedAt = hrtime(true);

        $response = $this->http
            ->baseUrl(
                (string) config('ai.providers.ollama.url'),
            )
            ->timeout(
                (int) config('ai.providers.ollama.timeout', 600),
            )
            ->retry(
                times: 2,
                sleepMilliseconds: 500,
                throw: false,
            )
            ->post(
                '/api/generate',
                $this->buildPayload($request),
            );

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf(
                    'Ollama request failed with HTTP %d: %s',
                    $response->status(),
                    $response->body(),
                ),
            );
        }

        $content = $response->json('response');

        if (blank($content)) {
            throw new RuntimeException(
                'Ollama returned an empty response.',
            );
        }

        if (
            $request->capability
            === LLMCapability::STRUCTURED_OUTPUT
        ) {
            $content = json_decode(
                json: $content,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        }

        $durationMs = (int) (
            (hrtime(true) - $startedAt) / 1_000_000
        );

        return new LLMResponse(
            content: $content,
            provider: $this->name(),
            model: $this->model(),
            inputTokens: $this->nullableInteger(
                $response->json('prompt_eval_count'),
            ),
            outputTokens: $this->nullableInteger(
                $response->json('eval_count'),
            ),
            durationMs: $durationMs,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(LLMRequest $request): array
    {
        $prompt = $request->prompt;

        if (filled($request->systemPrompt)) {
            $prompt = implode("\n\n", [
                $request->systemPrompt,
                $request->prompt,
            ]);
        }

        $payload = [
            'model' => $this->model(),
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $request->temperature,
            ],
        ];

        if ($request->maxTokens !== null) {
            $payload['options']['num_predict']
                = $request->maxTokens;
        }

        if (
            $request->capability
            === LLMCapability::STRUCTURED_OUTPUT
        ) {
            if ($request->jsonSchema === null) {
                throw new RuntimeException(
                    'A JSON schema is required for structured output.',
                );
            }

            $payload['format'] = $request->jsonSchema;
        }

        return $payload;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value)
            ? (int) $value
            : null;
    }
}
