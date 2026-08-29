<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Transports;

use Anklesh\AiPlatform\Contracts\AiTransport;
use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ProviderRoute;
use Laravel\Ai\Responses\StructuredAgentResponse;

use function Laravel\Ai\agent;

final class LaravelAiTransport implements AiTransport
{
    /** @param list<ProviderRoute> $routes */
    public function generate(AiRequest $request, array $routes): AiResponse
    {
        $response = agent(
            instructions: $request->instructions,
            messages: $request->messages,
            tools: $request->tools,
            schema: $request->schema,
        )->prompt(
            prompt: $request->prompt,
            attachments: $request->attachments,
            provider: $this->providerAndModelList($routes),
            timeout: $request->timeout,
        );

        $content = $response instanceof StructuredAgentResponse
            ? $response->toArray()
            : $response->text;

        return new AiResponse(
            content: $content,
            provider: $response->meta->provider ?? $routes[0]->provider,
            model: $response->meta->model ?? $routes[0]->model,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            metadata: [
                'invocation_id' => $response->invocationId,
                'cache_write_input_tokens' => $response->usage->cacheWriteInputTokens,
                'cache_read_input_tokens' => $response->usage->cacheReadInputTokens,
                'reasoning_tokens' => $response->usage->reasoningTokens,
            ],
        );
    }

    /**
     * @param  list<ProviderRoute>  $routes
     * @return array<string, string|null>
     */
    private function providerAndModelList(array $routes): array
    {
        $providers = [];

        foreach ($routes as $route) {
            $providers[$route->provider] = $route->model;
        }

        return $providers;
    }
}
