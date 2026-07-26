<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ManagementReviewType: string
{
    case Annual = 'annual';
    case SemiAnnual = 'semi_annual';
    case Quarterly = 'quarterly';
    case Site = 'site';
    case Product = 'product';
    case AdHoc = 'ad_hoc';
}
