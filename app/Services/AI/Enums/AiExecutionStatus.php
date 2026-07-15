<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum AiExecutionStatus: string
{
    case RUNNING = 'running';

    case SUCCEEDED = 'succeeded';

    case FAILED = 'failed';
}
