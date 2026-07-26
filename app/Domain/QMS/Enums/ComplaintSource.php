<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ComplaintSource: string
{
    case Patient = 'patient';
    case HealthcareProfessional = 'healthcare_professional';
    case Distributor = 'distributor';
    case Regulator = 'regulator';
    case Internal = 'internal';
    case Other = 'other';
}
