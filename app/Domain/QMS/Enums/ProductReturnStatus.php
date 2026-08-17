<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ProductReturnStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
    case UnderQuarantine = 'under_quarantine';
    case DispositionPending = 'disposition_pending';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
