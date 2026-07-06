<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\DocumentStatsOverview;
use App\Filament\Widgets\DocumentsByStatusChart;
use App\Filament\Widgets\DocumentsCreatedChart;
use App\Filament\Widgets\PendingApprovalsTable;
use App\Filament\Widgets\RecentAuditActivityTable;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string|Htmlable
    {
        $user = Auth::user();

        if ($user === null) {
            return parent::getTitle();
        }

        return "Welcome back, {$user->name}";
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            DocumentStatsOverview::class,
            DocumentsByStatusChart::class,
            DocumentsCreatedChart::class,
            PendingApprovalsTable::class,
            RecentAuditActivityTable::class,
        ];
    }

    /**
     * @return int|array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
