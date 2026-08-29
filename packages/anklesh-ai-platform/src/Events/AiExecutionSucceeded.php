<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Events;

use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Data\ExecutionContext;

final readonly class AiExecutionSucceeded
{
    public function __construct(
        public ExecutionContext $context,
        public AiResponse $response,
        public int $durationMs,
    ) {}
}
