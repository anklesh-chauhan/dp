<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ComplaintType: string
{
    case ProductQuality = 'product_quality';
    case AdverseEvent = 'adverse_event';
    case MedicalInformation = 'medical_information';
    case Distribution = 'distribution';
    case Other = 'other';
}
