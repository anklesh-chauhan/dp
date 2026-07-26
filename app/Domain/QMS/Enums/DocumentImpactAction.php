<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum DocumentImpactAction: string
{
    case Create = 'create';
    case Revise = 'revise';
    case Retire = 'retire';
    case NoChange = 'no_change';
}
