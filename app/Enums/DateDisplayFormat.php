<?php

declare(strict_types=1);

namespace App\Enums;

enum DateDisplayFormat: string
{
    case DayMonthYear = 'd/m/Y';
    case MonthDayYear = 'm/d/Y';
    case Iso = 'Y-m-d';
    case DayMonYear = 'd-M-Y';
    case DayMonthNameYear = 'd M Y';

    public function label(): string
    {
        return match ($this) {
            self::DayMonthYear => 'DD/MM/YYYY (13/08/2026)',
            self::MonthDayYear => 'MM/DD/YYYY (08/13/2026)',
            self::Iso => 'YYYY-MM-DD (2026-08-13)',
            self::DayMonYear => 'DD-Mon-YYYY (13-Aug-2026)',
            self::DayMonthNameYear => 'DD Month YYYY (13 Aug 2026)',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $format): array => [$format->value => $format->label()])
            ->all();
    }
}
