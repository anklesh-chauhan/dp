<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\QMS\Services\QualityMetricsService;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class QualityMetrics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?string $navigationLabel = 'Quality Metrics';

    protected static ?string $title = 'Quality Metrics';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'quality-metrics';

    protected static string|array $routeMiddleware = ['module:qms'];

    protected string $view = 'filament.pages.quality-metrics';

    /** @var array{lifecycles: array<string, array<string, int>>, overdue: array<string, int>} */
    public array $snapshot = [
        'lifecycles' => [],
        'overdue' => [],
    ];

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && (bool) auth()->user()?->can('View:QualityMetrics');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->snapshot = app(QualityMetricsService::class)->snapshot($user);
    }
}
