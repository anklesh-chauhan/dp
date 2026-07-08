<?php

declare(strict_types=1);

namespace App\Support\NumberSeries;

use App\Models\NumberSeries;
use App\Models\NumberSeriesSetting;

class NumberSeriesRegistry
{
    /** @var array<string, NumberSeriesDefinition> */
    private array $definitions = [];

    public function definition(string $documentTypeCode): NumberSeriesDefinition
    {
        $documentTypeCode = strtoupper($documentTypeCode);

        if (! isset($this->definitions[$documentTypeCode])) {
            $this->definitions[$documentTypeCode] = $this->resolveDefinition($documentTypeCode);
        }

        return $this->definitions[$documentTypeCode];
    }

    public function allowsExpandedPadding(): bool
    {
        return NumberSeriesSetting::current()->allowsExpandedPadding();
    }

    public function seriesKey(string $documentTypeCode, string $departmentCode): string
    {
        return strtoupper($documentTypeCode).':'.strtoupper($departmentCode);
    }

    private function resolveDefinition(string $documentTypeCode): NumberSeriesDefinition
    {
        $settings = NumberSeriesSetting::current();

        $numberSeries = NumberSeries::query()
            ->whereHas('documentType', fn ($query) => $query->where('code', $documentTypeCode))
            ->first();

        return new NumberSeriesDefinition(
            documentTypeCode: $documentTypeCode,
            prefixPattern: $numberSeries?->effectivePrefixPattern($settings) ?? $settings->default_prefix_pattern,
            paddingLength: $numberSeries?->effectivePaddingLength($settings) ?? $settings->default_padding_length,
            suffix: $numberSeries?->effectiveSuffix($settings) ?? $settings->default_suffix,
        );
    }
}
