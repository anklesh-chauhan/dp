<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SopDocument;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DocumentsCreatedChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Documents Created';

    protected ?string $description = 'New documents over the last six months.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getData(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $monthsAgo): Carbon => now()->subMonths($monthsAgo)->startOfMonth());

        $counts = $months->map(
            fn (Carbon $month): int => SopDocument::query()
                ->whereBetween('created_at', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ])
                ->count()
        );

        return [
            'datasets' => [
                [
                    'label' => 'Documents created',
                    'data' => $counts->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months->map(fn (Carbon $month): string => $month->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
