<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CapaStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case PendingEffectiveness = 'pending_effectiveness';
    case Effective = 'effective';
    case Ineffective = 'ineffective';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
