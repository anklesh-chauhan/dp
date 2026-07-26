<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum AuditFindingDisposition: string
{
    case Open = 'open';
    case ResponsePending = 'response_pending';
    case UnderVerification = 'under_verification';
    case Accepted = 'accepted';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
