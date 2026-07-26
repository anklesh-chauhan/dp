<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ComplaintStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
    case UnderAssessment = 'under_assessment';
    case UnderInvestigation = 'under_investigation';
    case ResponsePending = 'response_pending';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
