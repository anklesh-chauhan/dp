<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum RiskAssessmentType: string
{
    case Process = 'process';
    case Product = 'product';
    case Equipment = 'equipment';
    case Supplier = 'supplier';
    case Change = 'change';
    case ComputerizedSystem = 'computerized_system';
    case Other = 'other';
}
