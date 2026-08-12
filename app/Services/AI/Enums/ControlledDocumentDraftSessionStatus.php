<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum ControlledDocumentDraftSessionStatus: string
{
    case GATHERING = 'gathering';

    case PREVIEW_READY = 'preview_ready';

    case CONFIRMED = 'confirmed';

    case FAILED = 'failed';

    public function canChat(): bool
    {
        return in_array($this, [
            self::GATHERING,
            self::PREVIEW_READY,
        ], true);
    }
}
