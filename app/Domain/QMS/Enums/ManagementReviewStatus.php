<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ManagementReviewStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case MinutesPending = 'minutes_pending';
    case ActionsPending = 'actions_pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
