<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ProductQualityReviewType: string
{
    case Annual = 'annual';
    case Interim = 'interim';
}
