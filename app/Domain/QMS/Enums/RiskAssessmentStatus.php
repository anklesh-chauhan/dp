<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum RiskAssessmentStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case MitigationInProgress = 'mitigation_in_progress';
    case Monitoring = 'monitoring';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
