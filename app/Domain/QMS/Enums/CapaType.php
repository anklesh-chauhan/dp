<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CapaType: string
{
    case Corrective = 'corrective';
    case Preventive = 'preventive';
    case CorrectiveAndPreventive = 'corrective_and_preventive';
}
