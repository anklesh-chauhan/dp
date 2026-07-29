<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CsvTestStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Retired = 'retired';
}
