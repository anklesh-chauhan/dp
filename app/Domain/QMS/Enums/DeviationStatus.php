<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum DeviationStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case UnderInvestigation = 'under_investigation';
    case InvestigationComplete = 'investigation_complete';
    case CapaRequired = 'capa_required';
    case EffectivenessReview = 'effectiveness_review';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
