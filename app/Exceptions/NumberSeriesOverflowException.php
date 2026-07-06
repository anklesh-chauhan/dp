<?php

declare(strict_types=1);

namespace App\Exceptions;

class NumberSeriesOverflowException extends ServiceException
{
    public static function forSeries(string $seriesKey, int $number, int $maxPaddedValue): self
    {
        return new self(
            message: "Number series [{$seriesKey}] exceeded its padding limit at {$number}. Maximum padded value is {$maxPaddedValue}.",
            title: 'Number Series Overflow',
        );
    }

    public function title(): string
    {
        return $this->title ?? 'Number Series Overflow';
    }
}
