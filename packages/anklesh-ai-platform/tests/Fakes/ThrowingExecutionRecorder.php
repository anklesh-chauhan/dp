<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Tests\Fakes;

use Anklesh\AiPlatform\Contracts\ExecutionRecorder;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ExecutionContext;
use RuntimeException;
use Throwable;

final class ThrowingExecutionRecorder implements ExecutionRecorder
{
    public function started(ExecutionContext $context): void
    {
        throw new RuntimeException('Recorder failed.');
    }

    public function succeeded(ExecutionContext $context, AiResponse $response, int $durationMs): void
    {
        throw new RuntimeException('Recorder failed.');
    }

    public function failed(ExecutionContext $context, Throwable $exception, int $durationMs): void
    {
        throw new RuntimeException('Recorder failed.');
    }
}
