<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum SupplierRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
