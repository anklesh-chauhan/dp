<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum BatchReleaseStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Released = 'released';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
