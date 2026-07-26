<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum InternalAuditStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Reporting = 'reporting';
    case FollowUp = 'follow_up';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
