<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ComputerizedSystemIncidentSeverity: string
{
    case Minor = 'minor';
    case Major = 'major';
    case Critical = 'critical';
}
