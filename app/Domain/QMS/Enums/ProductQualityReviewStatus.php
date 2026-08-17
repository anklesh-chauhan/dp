<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ProductQualityReviewStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
