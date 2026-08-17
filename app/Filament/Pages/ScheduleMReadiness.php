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

final class ScheduleMReadiness extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?string $navigationLabel = 'Schedule M Readiness';

    protected static ?string $title = 'Schedule M Readiness';

    protected static ?int $navigationSort = 24;

    protected static ?string $slug = 'schedule-m-readiness';

    protected static string|array $routeMiddleware = ['module:qms'];

    protected string $view = 'filament.pages.schedule-m-readiness';

    /**
     * @var array{
     *     gap_assessment: array{
     *         assessment_number: string|null,
     *         status: string|null,
     *         total_items: int,
     *         compliant_items: int,
     *         percent_compliant: int
     *     }|null,
     *     overdue_calibrations: int|null,
     *     open_critical_audit_findings: int,
     *     open_deviations: int,
     *     overdue_deviations: int,
     *     open_capas: int,
     *     overdue_capas: int
     * }
     */
    public array $readiness = [
        'gap_assessment' => null,
        'overdue_calibrations' => null,
        'open_critical_audit_findings' => 0,
        'open_deviations' => 0,
        'overdue_deviations' => 0,
        'open_capas' => 0,
        'overdue_capas' => 0,
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

        $this->readiness = app(QualityMetricsService::class)->scheduleMReadiness($user);
    }
}
