<?php

declare(strict_types=1);

namespace App\Services\NumberSeries;

use App\Exceptions\NumberSeriesOverflowException;
use App\Models\Department;
use App\Models\NumberSeriesCounter;
use App\Support\NumberSeries\NumberSeriesDefinition;
use App\Support\NumberSeries\NumberSeriesRegistry;
use Illuminate\Support\Facades\DB;

class NumberSeriesService
{
    public function __construct(
        private readonly NumberSeriesRegistry $registry,
    ) {}

    public function generate(Department $department, string $documentTypeCode): string
    {
        $documentTypeCode = strtoupper($documentTypeCode);
        $definition = $this->registry->definition($documentTypeCode);
        $seriesKey = $this->registry->seriesKey($documentTypeCode, $department->code);
        $nextNumber = $this->incrementCounter($seriesKey, $definition);

        return $definition->format($nextNumber, $department->code, $this->allowsExpandedPadding());
    }

    public function peekNext(Department $department, string $documentTypeCode): string
    {
        $documentTypeCode = strtoupper($documentTypeCode);
        $definition = $this->registry->definition($documentTypeCode);
        $seriesKey = $this->registry->seriesKey($documentTypeCode, $department->code);

        $counter = NumberSeriesCounter::query()
            ->where('series_type', $seriesKey)
            ->first();

        $nextNumber = ($counter?->last_number ?? 0) + 1;

        return $definition->format($nextNumber, $department->code, $this->allowsExpandedPadding());
    }

    public function synchronizeCounter(Department $department, string $documentTypeCode, int $lastNumber): void
    {
        $seriesKey = $this->registry->seriesKey(
            strtoupper($documentTypeCode),
            $department->code,
        );

        NumberSeriesCounter::query()->updateOrCreate(
            ['series_type' => $seriesKey],
            ['last_number' => max(0, $lastNumber)],
        );
    }

    private function incrementCounter(string $seriesKey, NumberSeriesDefinition $definition): int
    {
        return DB::transaction(function () use ($seriesKey, $definition): int {
            $counter = NumberSeriesCounter::query()
                ->where('series_type', $seriesKey)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $counter = NumberSeriesCounter::query()->create([
                    'series_type' => $seriesKey,
                    'last_number' => 0,
                ]);

                $counter = NumberSeriesCounter::query()
                    ->whereKey($counter->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $nextNumber = $counter->last_number + 1;
            $this->guardAgainstOverflow($definition, $seriesKey, $nextNumber);

            $counter->update(['last_number' => $nextNumber]);

            return $nextNumber;
        });
    }

    private function guardAgainstOverflow(NumberSeriesDefinition $definition, string $seriesKey, int $number): void
    {
        if ($number <= $definition->maxPaddedValue() || $this->allowsExpandedPadding()) {
            return;
        }

        throw NumberSeriesOverflowException::forSeries(
            $seriesKey,
            $number,
            $definition->maxPaddedValue(),
        );
    }

    private function allowsExpandedPadding(): bool
    {
        return config('number-series.overflow_behavior', 'expand') === 'expand';
    }
}
