<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ValidationMasterPlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case UnderRevision = 'under_revision';
    case Retired = 'retired';
    case Cancelled = 'cancelled';
}
