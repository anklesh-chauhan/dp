<?php

declare(strict_types=1);

namespace App\Services\AI\Routing;

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Contracts\AiExecutionRecorder;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Contracts\LLMProvider;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Exceptions\AllProvidersFailedException;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class LLMManager implements LLMManagerContract
{
    public function __construct(
        private ProviderRouter $router,
        private AiExecutionRecorder $recorder,
    ) {}

    public function generate(
        LLMRequest $request,
    ): LLMResponse {
        $executionStartedAt = hrtime(true);

        $execution = $this->observe(
            fn (): AiExecution => $this->recorder->startExecution(
                $request,
            ),
        );

        $providers = $this->router->providersFor($request);

        $failures = [];
        $attemptCount = 0;

        foreach ($providers as $provider) {
            $attemptCount++;

            $attempt = $this->observe(
                fn (): AiExecutionAttempt => $this->recorder->startAttempt(
                    $execution,
                    $provider,
                    $attemptCount,
                ),
                enabled: $execution instanceof AiExecution,
            );

            $attemptStartedAt = hrtime(true);

            try {
                $response = $provider->generate($request);

                $attemptDurationMs = $this->elapsedMilliseconds(
                    $attemptStartedAt,
                );

                $this->observe(
                    fn (): null => $this->completeAttempt(
                        $attempt,
                        $response,
                        $attemptDurationMs,
                    ),
                    enabled: $attempt instanceof AiExecutionAttempt,
                );

                $executionDurationMs = $this->elapsedMilliseconds(
                    $executionStartedAt,
                );

                $this->observe(
                    fn (): null => $this->completeExecution(
                        $execution,
                        $response,
                        $attemptCount,
                        $executionDurationMs,
                    ),
                    enabled: $execution instanceof AiExecution,
                );

                Log::info('LLM generation succeeded.', [
                    'provider' => $response->provider,
                    'model' => $response->model,
                    'use_case' => $request->useCase->value,
                    'capability' => $request->capability->value,
                    'duration_ms' => $response->durationMs,
                ]);

                return $response;
            } catch (Throwable $exception) {
                $attemptDurationMs = $this->elapsedMilliseconds(
                    $attemptStartedAt,
                );

                $this->observe(
                    fn (): null => $this->failAttempt(
                        $attempt,
                        $exception,
                        $attemptDurationMs,
                    ),
                    enabled: $attempt instanceof AiExecutionAttempt,
                );

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

        $executionDurationMs = $this->elapsedMilliseconds(
            $executionStartedAt,
        );

        $this->observe(
            fn (): null => $this->failExecution(
                $execution,
                $attemptCount,
                $executionDurationMs,
            ),
            enabled: $execution instanceof AiExecution,
        );

        throw AllProvidersFailedException::fromFailures(
            $failures,
        );
    }

    private function completeAttempt(
        AiExecutionAttempt $attempt,
        LLMResponse $response,
        int $durationMs,
    ): null {
        $this->recorder->completeAttempt(
            $attempt,
            $response,
            $durationMs,
        );

        return null;
    }

    private function failAttempt(
        AiExecutionAttempt $attempt,
        Throwable $exception,
        int $durationMs,
    ): null {
        $this->recorder->failAttempt(
            $attempt,
            $exception,
            $durationMs,
        );

        return null;
    }

    private function completeExecution(
        AiExecution $execution,
        LLMResponse $response,
        int $attemptCount,
        int $durationMs,
    ): null {
        $this->recorder->completeExecution(
            $execution,
            $response,
            $attemptCount,
            $durationMs,
        );

        return null;
    }

    private function failExecution(
        AiExecution $execution,
        int $attemptCount,
        int $durationMs,
    ): null {
        $this->recorder->failExecution(
            $execution,
            $attemptCount,
            $durationMs,
        );

        return null;
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return max(
            0,
            (int) ((hrtime(true) - $startedAt) / 1_000_000),
        );
    }

    private function observe(
        Closure $callback,
        bool $enabled = true,
    ): mixed {
        if (! $enabled) {
            return null;
        }

        try {
            return $callback();
        } catch (Throwable $exception) {
            Log::error('AI observability recording failed.', [
                'exception' => $exception,
            ]);

            return null;
        }
    }
}
