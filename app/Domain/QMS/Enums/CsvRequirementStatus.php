<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CsvRequirementStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Superseded = 'superseded';
    case Retired = 'retired';
}
