<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ProductRecallType: string
{
    case Market = 'market';
    case Mock = 'mock';
    case Simulated = 'simulated';
}
