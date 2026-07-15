<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

use Carbon\CarbonImmutable;

enum AiObservabilityTimeWindow: string
{
    case LAST_24_HOURS = '24_hours';

    case LAST_7_DAYS = '7_days';

    case LAST_30_DAYS = '30_days';

    case LAST_90_DAYS = '90_days';

    case ALL_TIME = 'all_time';

    public function label(): string
    {
        return match ($this) {
            self::LAST_24_HOURS => 'Last 24 Hours',
            self::LAST_7_DAYS => 'Last 7 Days',
            self::LAST_30_DAYS => 'Last 30 Days',
            self::LAST_90_DAYS => 'Last 90 Days',
            self::ALL_TIME => 'All Time',
        };
    }

    public function startsAt(): ?CarbonImmutable
    {
        return match ($this) {
            self::LAST_24_HOURS => now()->toImmutable()->subHours(24),
            self::LAST_7_DAYS => now()->toImmutable()->subDays(7),
            self::LAST_30_DAYS => now()->toImmutable()->subDays(30),
            self::LAST_90_DAYS => now()->toImmutable()->subDays(90),
            self::ALL_TIME => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(
                fn (self $window): array => [
                    $window->value => $window->label(),
                ],
            )
            ->all();
    }
}
