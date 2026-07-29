<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CsvSpecificationType: string
{
    case Functional = 'functional';
    case Configuration = 'configuration';
    case Design = 'design';
    case Interface = 'interface';
    case Security = 'security';
    case Data = 'data';
}
