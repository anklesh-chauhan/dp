<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiExecutions\Widgets;

use App\Enums\ProductModule;
use App\Filament\Concerns\RequiresProductModule;
use App\Models\AiExecution;
use App\Services\AI\Enums\AiExecutionStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class AiExecutionOverview extends StatsOverviewWidget
{
    use RequiresProductModule;

    public static function productModule(): ProductModule
    {
        return ProductModule::AI;
    }

    protected function getStats(): array
    {
        $metrics = AiExecution::query()
            ->selectRaw('COUNT(*) as total_executions')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful_executions',
                [AiExecutionStatus::SUCCEEDED->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_executions',
                [AiExecutionStatus::FAILED->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN attempt_count > 1 THEN 1 ELSE 0 END) as fallback_executions',
            )
            ->selectRaw('AVG(duration_ms) as average_duration_ms')
            ->selectRaw('COALESCE(SUM(input_tokens), 0) as total_input_tokens')
            ->selectRaw('COALESCE(SUM(output_tokens), 0) as total_output_tokens')
            ->first();

        $totalExecutions = (int) ($metrics?->total_executions ?? 0);

        $successfulExecutions = (int) ($metrics?->successful_executions ?? 0);

        $failedExecutions = (int) ($metrics?->failed_executions ?? 0);

        $fallbackExecutions = (int) ($metrics?->fallback_executions ?? 0);

        $averageDurationMs = $metrics?->average_duration_ms !== null
            ? (int) round((float) $metrics->average_duration_ms)
            : null;

        $totalTokens = (int) ($metrics?->total_input_tokens ?? 0)
            + (int) ($metrics?->total_output_tokens ?? 0);

        return [
            Stat::make(
                'Total Executions',
                number_format($totalExecutions),
            )
                ->description('Recorded AI executions')
                ->icon('heroicon-m-cpu-chip'),

            Stat::make(
                'Success Rate',
                $this->formatPercentage(
                    $successfulExecutions,
                    $totalExecutions,
                ),
            )
                ->description(
                    number_format($successfulExecutions).' successful',
                )
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(
                'Failure Rate',
                $this->formatPercentage(
                    $failedExecutions,
                    $totalExecutions,
                ),
            )
                ->description(
                    number_format($failedExecutions).' failed',
                )
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make(
                'Fallback Rate',
                $this->formatPercentage(
                    $fallbackExecutions,
                    $totalExecutions,
                ),
            )
                ->description(
                    number_format($fallbackExecutions).' used fallback',
                )
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),

            Stat::make(
                'Average Duration',
                $this->formatDuration($averageDurationMs),
            )
                ->description('Across recorded durations')
                ->icon('heroicon-m-clock'),

            Stat::make(
                'Total Tokens',
                number_format($totalTokens),
            )
                ->description('Input and output tokens')
                ->icon('heroicon-m-circle-stack'),
        ];
    }

    private function formatPercentage(
        int $value,
        int $total,
    ): string {
        if ($total === 0) {
            return '0.0%';
        }

        return number_format(
            ($value / $total) * 100,
            1,
        ).'%';
    }

    private function formatDuration(?int $durationMs): string
    {
        if ($durationMs === null) {
            return '—';
        }

        if ($durationMs < 1_000) {
            return "{$durationMs} ms";
        }

        return number_format(
            $durationMs / 1_000,
            2,
        ).' s';
    }
}
