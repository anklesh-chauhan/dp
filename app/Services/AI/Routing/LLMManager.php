<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Exceptions\AllProvidersFailedException;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class LLMManager
{
    public function __construct(
        private ProviderRouter $router,
    ) {}

    public function generate(
        LLMRequest $request,
    ): LLMResponse {
        $providers = $this->router->providersFor($request);

        $failures = [];

        foreach ($providers as $provider) {
            try {
                $response = $provider->generate($request);

                Log::info('LLM generation succeeded.', [
                    'provider' => $response->provider,
                    'model' => $response->model,
                    'use_case' => $request->useCase->value,
                    'capability' => $request->capability->value,
                    'duration_ms' => $response->durationMs,
                ]);

                return $response;
            } catch (Throwable $exception) {
                $failures[$provider->name()] = [
                    'message' => $exception->getMessage(),
                    'exception' => $exception::class,
                ];

                Log::warning('LLM provider failed.', [
                    'provider' => $provider->name(),
                    'model' => $provider->model(),
                    'use_case' => $request->useCase->value,
                    'exception' => $exception,
                ]);
            }
        }

        throw AllProvidersFailedException::fromFailures(
            $failures,
        );
    }
}
