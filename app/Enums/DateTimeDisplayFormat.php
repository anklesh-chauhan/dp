<?php

declare(strict_types=1);

namespace App\Enums;

enum DateTimeDisplayFormat: string
{
    case DayMonthYearHm = 'd/m/Y H:i';
    case MonthDayYearHm = 'm/d/Y H:i';
    case IsoHm = 'Y-m-d H:i';
    case DayMonYearHm = 'd-M-Y H:i';
    case DayMonthNameYearHm = 'd M Y H:i';

    public function label(): string
    {
        return match ($this) {
            self::DayMonthYearHm => 'DD/MM/YYYY HH:mm (13/08/2026 14:30)',
            self::MonthDayYearHm => 'MM/DD/YYYY HH:mm (08/13/2026 14:30)',
            self::IsoHm => 'YYYY-MM-DD HH:mm (2026-08-13 14:30)',
            self::DayMonYearHm => 'DD-Mon-YYYY HH:mm (13-Aug-2026 14:30)',
            self::DayMonthNameYearHm => 'DD Month YYYY HH:mm (13 Aug 2026 14:30)',
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
