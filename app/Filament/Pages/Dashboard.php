<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\ProductModule;
use App\Filament\Widgets\DocumentsByStatusChart;
use App\Filament\Widgets\DocumentsCreatedChart;
use App\Filament\Widgets\DocumentStatsOverview;
use App\Filament\Widgets\PendingApprovalsTable;
use App\Filament\Widgets\RecentAuditActivityTable;
use App\Support\Modules\ModuleManager;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'DMS Dashboard';

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::DMS);
    }

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
