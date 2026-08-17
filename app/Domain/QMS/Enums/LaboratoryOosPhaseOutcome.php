<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum LaboratoryOosPhaseOutcome: string
{
    case AssignableError = 'assignable_error';
    case ConfirmedOos = 'confirmed_oos';
    case Inconclusive = 'inconclusive';
    case Pending = 'pending';
}
