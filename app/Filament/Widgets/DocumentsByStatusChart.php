<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\DocumentStatus;
use Filament\Widgets\ChartWidget;

class DocumentsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Documents by Status';

    protected ?string $description = 'Distribution across the document lifecycle.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getData(): array
    {
        $statuses = DocumentStatus::query()
            ->withCount('documents')
            ->orderBy('sort_order')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Documents',
                    'data' => $statuses->pluck('documents_count')->all(),
                    'backgroundColor' => [
                        '#9ca3af',
                        '#fbbf24',
                        '#34d399',
                        '#10b981',
                        '#f87171',
                        '#fb923c',
                        '#a78bfa',
                        '#64748b',
                        '#ef4444',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $statuses->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
