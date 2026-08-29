<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Tests\Fakes;

use Anklesh\AiPlatform\Contracts\ExecutionRecorder;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ExecutionContext;
use Throwable;

final class RecordingExecutionRecorder implements ExecutionRecorder
{
    public ?ExecutionContext $startedContext = null;

    public ?ExecutionContext $succeededContext = null;

    public ?ExecutionContext $failedContext = null;

    public ?AiResponse $response = null;

    public ?Throwable $exception = null;

    public function started(ExecutionContext $context): void
    {
        $this->startedContext = $context;
    }

    public function succeeded(ExecutionContext $context, AiResponse $response, int $durationMs): void
    {
        $this->succeededContext = $context;
        $this->response = $response;
    }

    public function failed(ExecutionContext $context, Throwable $exception, int $durationMs): void
    {
        $this->failedContext = $context;
        $this->exception = $exception;
    }
}
