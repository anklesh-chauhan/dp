<?php

declare(strict_types=1);

namespace App\Support\NumberSeries;

readonly class NumberSeriesDefinition
{
    public function __construct(
        public string $documentTypeCode,
        public string $prefixPattern,
        public int $paddingLength,
        public string $suffix,
    ) {}

    public function buildPrefix(string $departmentCode): string
    {
        return str_replace(
            ['{type}', '{department}'],
            [strtoupper($this->documentTypeCode), strtoupper($departmentCode)],
            $this->prefixPattern,
        );
    }

    public function format(int $number, string $departmentCode, bool $allowExpandedPadding = true): string
    {
        $numberString = (string) $number;
        $padding = $this->paddingLength;

        if (strlen($numberString) > $padding) {
            if (! $allowExpandedPadding) {
                return $this->buildPrefix($departmentCode).$numberString.$this->suffix;
            }

            $padding = strlen($numberString);
        }

        return $this->buildPrefix($departmentCode)
            .str_pad($numberString, $padding, '0', STR_PAD_LEFT)
            .$this->suffix;
    }

    public function maxPaddedValue(): int
    {
        return (10 ** $this->paddingLength) - 1;
    }
}
