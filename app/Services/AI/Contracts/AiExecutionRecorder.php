<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use Throwable;

interface AiExecutionRecorder
{
    public function startExecution(
        LLMRequest $request,
    ): AiExecution;

    public function startAttempt(
        AiExecution $execution,
        LLMProvider $provider,
        int $sequence,
    ): AiExecutionAttempt;

    public function completeAttempt(
        AiExecutionAttempt $attempt,
        LLMResponse $response,
        int $durationMs,
    ): void;

    public function failAttempt(
        AiExecutionAttempt $attempt,
        Throwable $exception,
        int $durationMs,
    ): void;

    public function completeExecution(
        AiExecution $execution,
        LLMResponse $response,
        int $attemptCount,
        int $durationMs,
    ): void;

    public function failExecution(
        AiExecution $execution,
        int $attemptCount,
        int $durationMs,
    ): void;
}
