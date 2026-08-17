<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum SiteMasterFileStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Retired = 'retired';
}
