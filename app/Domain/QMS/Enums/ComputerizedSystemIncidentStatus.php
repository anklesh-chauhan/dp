<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ComputerizedSystemIncidentStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
