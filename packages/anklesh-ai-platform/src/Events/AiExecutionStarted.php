<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Events;

use Anklesh\AiPlatform\Data\ExecutionContext;

final readonly class AiExecutionStarted
{
    public function __construct(public ExecutionContext $context) {}
}
