<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum InvestigationStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case PendingReview = 'pending_review';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
