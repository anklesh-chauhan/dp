<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\LLMProvider;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Providers\Support\GeminiSchemaNormalizer;
use Illuminate\Http\Client\Factory as HttpFactory;
use JsonException;
use RuntimeException;

final readonly class GeminiProvider implements LLMProvider
{
    public function __construct(
        private HttpFactory $http,
        private GeminiSchemaNormalizer $schemaNormalizer,
    ) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function model(): string
    {
        return (string) config('ai.providers.gemini.model');
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
        $apiKey = (string) config('ai.providers.gemini.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException(
                'Gemini API key is not configured.',
            );
        }

        $startedAt = hrtime(true);

        $response = $this->http
            ->baseUrl(
                'https://generativelanguage.googleapis.com/v1beta',
            )
            ->timeout(
                (int) config('ai.providers.gemini.timeout', 180),
            )
            ->retry(
                times: 2,
                sleepMilliseconds: 500,
                throw: false,
            )
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post(
                sprintf(
                    '/models/%s:generateContent?key=%s',
                    $this->model(),
                    $apiKey,
                ),
                $this->buildPayload($request),
            );

        if ($response->failed()) {
            $message = $response->json('error.message')
                ?? $response->body();

            throw new RuntimeException(
                sprintf(
                    'Gemini request failed with HTTP %d: %s',
                    $response->status(),
                    $message,
                ),
            );
        }

        $content = $response->json(
            'candidates.0.content.parts.0.text',
        );

        if (blank($content)) {
            throw new RuntimeException(
                'Gemini returned an empty response.',
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
                $response->json(
                    'usageMetadata.promptTokenCount',
                ),
            ),
            outputTokens: $this->nullableInteger(
                $response->json(
                    'usageMetadata.candidatesTokenCount',
                ),
            ),
            durationMs: $durationMs,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(LLMRequest $request): array
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $request->prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $request->temperature,
            ],
        ];

        if ($request->maxTokens !== null) {
            $payload['generationConfig']['maxOutputTokens']
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

            $payload['generationConfig']['responseMimeType']
                = 'application/json';

            $payload['generationConfig']['responseSchema']
                = $this->schemaNormalizer->normalize(
                    $request->jsonSchema,
                );
        }

        if (filled($request->systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    [
                        'text' => $request->systemPrompt,
                    ],
                ],
            ];
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
