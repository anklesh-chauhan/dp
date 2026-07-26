<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ChangeControlStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Implementing = 'implementing';
    case EffectivenessReview = 'effectiveness_review';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
