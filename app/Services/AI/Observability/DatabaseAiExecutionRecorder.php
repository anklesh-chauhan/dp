<?php

declare(strict_types=1);

namespace App\Services\AI\Observability;

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Contracts\AiExecutionRecorder;
use App\Services\AI\Contracts\LLMProvider;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AiExecutionStatus;
use Throwable;

final readonly class DatabaseAiExecutionRecorder implements AiExecutionRecorder
{
    public function startExecution(
        LLMRequest $request,
    ): AiExecution {
        return AiExecution::query()->create([
            'use_case' => $request->useCase,
            'capability' => $request->capability,
            'status' => AiExecutionStatus::RUNNING,
            'attempt_count' => 0,
            'successful_provider' => null,
            'successful_model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'duration_ms' => null,
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
        ]);
    }

    public function startAttempt(
        AiExecution $execution,
        LLMProvider $provider,
        int $sequence,
    ): AiExecutionAttempt {
        return $execution->attempts()->create([
            'sequence' => $sequence,
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'status' => AiExecutionStatus::RUNNING,
            'input_tokens' => null,
            'output_tokens' => null,
            'duration_ms' => null,
            'exception_class' => null,
            'error_message' => null,
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
        ]);
    }

    public function completeAttempt(
        AiExecutionAttempt $attempt,
        LLMResponse $response,
        int $durationMs,
    ): void {
        $attempt->forceFill([
            'status' => AiExecutionStatus::SUCCEEDED,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'duration_ms' => $this->normalizeDuration($durationMs),
            'exception_class' => null,
            'error_message' => null,
            'completed_at' => now(),
            'failed_at' => null,
        ])->save();
    }

    public function failAttempt(
        AiExecutionAttempt $attempt,
        Throwable $exception,
        int $durationMs,
    ): void {
        $attempt->forceFill([
            'status' => AiExecutionStatus::FAILED,
            'input_tokens' => null,
            'output_tokens' => null,
            'duration_ms' => $this->normalizeDuration($durationMs),
            'exception_class' => $exception::class,
            'error_message' => $this->sanitizeErrorMessage(
                $exception->getMessage(),
            ),
            'completed_at' => null,
            'failed_at' => now(),
        ])->save();
    }

    public function completeExecution(
        AiExecution $execution,
        LLMResponse $response,
        int $attemptCount,
        int $durationMs,
    ): void {
        $execution->forceFill([
            'status' => AiExecutionStatus::SUCCEEDED,
            'attempt_count' => $this->normalizeAttemptCount($attemptCount),
            'successful_provider' => $response->provider,
            'successful_model' => $response->model,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'duration_ms' => $this->normalizeDuration($durationMs),
            'completed_at' => now(),
            'failed_at' => null,
        ])->save();
    }

    public function failExecution(
        AiExecution $execution,
        int $attemptCount,
        int $durationMs,
    ): void {
        $execution->forceFill([
            'status' => AiExecutionStatus::FAILED,
            'attempt_count' => $this->normalizeAttemptCount($attemptCount),
            'successful_provider' => null,
            'successful_model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'duration_ms' => $this->normalizeDuration($durationMs),
            'completed_at' => null,
            'failed_at' => now(),
        ])->save();
    }

    private function normalizeAttemptCount(int $attemptCount): int
    {
        return max(0, $attemptCount);
    }

    private function normalizeDuration(int $durationMs): int
    {
        return max(0, $durationMs);
    }

    private function sanitizeErrorMessage(string $message): string
    {
        return mb_substr($message, 0, 10_000);
    }
}
