<?php

declare(strict_types=1);

namespace App\Enums;

enum TimeDisplayFormat: string
{
    case TwentyFourHour = 'H:i';
    case TwentyFourHourWithSeconds = 'H:i:s';
    case TwelveHour = 'h:i A';

    public function label(): string
    {
        return match ($this) {
            self::TwentyFourHour => '24-hour (14:30)',
            self::TwentyFourHourWithSeconds => '24-hour with seconds (14:30:45)',
            self::TwelveHour => '12-hour (02:30 PM)',
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
