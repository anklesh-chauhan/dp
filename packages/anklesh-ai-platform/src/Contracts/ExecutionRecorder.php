<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Contracts;

use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ExecutionContext;
use Throwable;

interface ExecutionRecorder
{
    public function started(ExecutionContext $context): void;

    public function succeeded(ExecutionContext $context, AiResponse $response, int $durationMs): void;

    public function failed(ExecutionContext $context, Throwable $exception, int $durationMs): void;
}
