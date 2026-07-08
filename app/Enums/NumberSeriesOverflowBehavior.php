<?php

declare(strict_types=1);

namespace App\Enums;

enum NumberSeriesOverflowBehavior: string
{
    case Expand = 'expand';
    case Throw = 'throw';

    public function label(): string
    {
        return match ($this) {
            self::Expand => 'Expand padding',
            self::Throw => 'Throw exception',
        };
    }
}
