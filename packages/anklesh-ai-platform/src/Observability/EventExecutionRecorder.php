<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Observability;

use Anklesh\AiPlatform\Contracts\ExecutionRecorder;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ExecutionContext;
use Anklesh\AiPlatform\Events\AiExecutionFailed;
use Anklesh\AiPlatform\Events\AiExecutionStarted;
use Anklesh\AiPlatform\Events\AiExecutionSucceeded;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Throwable;

final readonly class EventExecutionRecorder implements ExecutionRecorder
{
    public function __construct(
        private Dispatcher $events,
        private Repository $config,
    ) {}

    public function started(ExecutionContext $context): void
    {
        $this->events->dispatch(new AiExecutionStarted($context));
    }

    public function succeeded(ExecutionContext $context, AiResponse $response, int $durationMs): void
    {
        $this->events->dispatch(new AiExecutionSucceeded($context, $response, $durationMs));
    }

    public function failed(ExecutionContext $context, Throwable $exception, int $durationMs): void
    {
        $this->events->dispatch(new AiExecutionFailed(
            context: $context,
            exceptionClass: $exception::class,
            message: $this->errorMessage($exception),
            durationMs: $durationMs,
        ));
    }

    private function errorMessage(Throwable $exception): string
    {
        if (! $this->config->get('anklesh-ai-platform.observability.include_error_messages', false)) {
            return 'AI execution failed.';
        }

        return Str::limit($exception->getMessage(), 10_000, '');
    }
}
