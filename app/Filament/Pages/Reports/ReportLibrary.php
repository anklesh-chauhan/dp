<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\ProductModule;
use App\Filament\Support\OperationalReportCatalog;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class ReportLibrary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'DMS · Reports';

    protected static ?string $navigationLabel = 'Report Library';

    protected static ?string $title = 'DMS Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'dms-reports';

    protected string $view = 'filament.pages.report-library';

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::DMS)
            && app(OperationalReportCatalog::class)->forModule(ProductModule::DMS)->isNotEmpty();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return Collection<int, array{title: string, description: string, url: string}>
     */
    public function reports(): Collection
    {
        return app(OperationalReportCatalog::class)
            ->forModule(ProductModule::DMS)
            ->map(fn ($report): array => [
                'title' => $report->title,
                'description' => $report->description,
                'url' => $report->url(),
            ]);
    }
}
