<?php

declare(strict_types=1);

namespace App\Support\Formatting;

use App\Enums\DateDisplayFormat;
use App\Enums\DateTimeDisplayFormat;
use App\Enums\TimeDisplayFormat;
use App\Models\Organization;
use Illuminate\Support\Carbon;

final class DateFormatSettings
{
    public function date(): string
    {
        return $this->resolve(
            Organization::defaultActive()?->date_display_format,
            (string) config('formatting.date', DateDisplayFormat::DayMonthYear->value),
            DateDisplayFormat::class,
            DateDisplayFormat::DayMonthYear->value,
        );
    }

    public function dateTime(): string
    {
        return $this->resolve(
            Organization::defaultActive()?->datetime_display_format,
            (string) config('formatting.datetime', DateTimeDisplayFormat::DayMonthYearHm->value),
            DateTimeDisplayFormat::class,
            DateTimeDisplayFormat::DayMonthYearHm->value,
        );
    }

    public function time(): string
    {
        return $this->resolve(
            Organization::defaultActive()?->time_display_format,
            (string) config('formatting.time', TimeDisplayFormat::TwentyFourHour->value),
            TimeDisplayFormat::class,
            TimeDisplayFormat::TwentyFourHour->value,
        );
    }

    public function timezone(): string
    {
        $timezone = Organization::defaultActive()?->timezone;

        return filled($timezone) ? (string) $timezone : (string) config('app.timezone', 'UTC');
    }

    public function formatDate(mixed $value, ?string $timezone = null): ?string
    {
        // Date-only values keep the stored calendar day unless a timezone is explicit.
        return $this->format($value, $this->date(), $timezone);
    }

    public function formatDateTime(mixed $value, ?string $timezone = null): ?string
    {
        return $this->format($value, $this->dateTime(), $timezone ?? $this->timezone());
    }

    public function formatTime(mixed $value, ?string $timezone = null): ?string
    {
        return $this->format($value, $this->time(), $timezone ?? $this->timezone());
    }

    /**
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function resolve(?string $organizationFormat, string $configuredFormat, string $enumClass, string $fallback): string
    {
        foreach ([$organizationFormat, $configuredFormat] as $candidate) {
            if (is_string($candidate) && $candidate !== '' && $enumClass::tryFrom($candidate) !== null) {
                return $candidate;
            }
        }

        return $fallback;
    }

    private function format(mixed $value, string $format, ?string $timezone): ?string
    {
        if (blank($value)) {
            return null;
        }

        $date = $value instanceof Carbon
            ? $value->copy()
            : Carbon::parse($value);

        if (filled($timezone)) {
            $date->setTimezone($timezone);
        }

        return $date->translatedFormat($format);
    }
}
