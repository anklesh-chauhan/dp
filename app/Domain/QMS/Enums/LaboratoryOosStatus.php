<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum LaboratoryOosStatus: string
{
    case Draft = 'draft';
    case PhaseOne = 'phase_one';
    case PhaseTwo = 'phase_two';
    case InvalidationProposed = 'invalidation_proposed';
    case Confirmed = 'confirmed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
