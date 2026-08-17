<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ScheduleMGapItemStatus: string
{
    case NotAssessed = 'not_assessed';
    case Compliant = 'compliant';
    case Partial = 'partial';
    case Gap = 'gap';
    case NotApplicable = 'not_applicable';
}
