<?php

declare(strict_types=1);

namespace App\Support\NumberSeries;

class NumberSeriesRegistry
{
    public function definition(string $documentTypeCode): NumberSeriesDefinition
    {
        $documentTypeCode = strtoupper($documentTypeCode);
        $default = config('number-series.default', []);
        $typeConfig = config("number-series.types.{$documentTypeCode}", []);
        $merged = array_merge($default, $typeConfig);

        return new NumberSeriesDefinition(
            documentTypeCode: $documentTypeCode,
            prefixPattern: (string) ($merged['prefix_pattern'] ?? '{type}-{department}-'),
            paddingLength: (int) ($merged['padding_length'] ?? 5),
            suffix: (string) ($merged['suffix'] ?? ''),
        );
    }

    public function seriesKey(string $documentTypeCode, string $departmentCode): string
    {
        return strtoupper($documentTypeCode).':'.strtoupper($departmentCode);
    }
}
