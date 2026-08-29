<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Events;

use Anklesh\AiPlatform\Data\ExecutionContext;

final readonly class AiExecutionFailed
{
    public function __construct(
        public ExecutionContext $context,
        public string $exceptionClass,
        public string $message,
        public int $durationMs,
    ) {}
}
