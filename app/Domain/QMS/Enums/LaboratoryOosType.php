<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum LaboratoryOosType: string
{
    case Oos = 'oos';
    case Oot = 'oot';
}
