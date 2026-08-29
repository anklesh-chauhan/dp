<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum ControlledDocumentDraftRequestStatus: string
{
    case QUEUED = 'queued';

    case PROCESSING = 'processing';

    case COMPLETED = 'completed';

    case FAILED = 'failed';

    public function isActive(): bool
    {
        return in_array($this, [self::QUEUED, self::PROCESSING], true);
    }
}
