<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Domain\Reporting\Services\OperationalReportCsvExporter;
use App\Enums\ProductModule;
use App\Support\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

abstract class OperationalReportPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.operational-report';

    abstract public static function productModule(): ProductModule;

    abstract public static function reportPermission(): string;

    abstract protected function exportFilename(): string;

    /**
     * @return list<string>
     */
    abstract protected function exportHeaders(): array;

    /**
     * @return iterable<int, list<string|int|float|null>>
     */
    abstract protected function exportRows(): iterable;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return match (static::productModule()) {
            ProductModule::DMS => 'DMS · Reports',
            ProductModule::QMS => 'QMS · Reports',
            ProductModule::AI => 'AI Management',
        };
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && app(ModuleManager::class)->enabled(static::productModule())
            && $user->can(static::reportPermission());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function exportCsvAction(): Action
    {
        return Action::make('exportCsv')
            ->label('Export CSV')
            ->icon(Heroicon::ArrowDownTray)
            ->action(fn (): StreamedResponse => app(OperationalReportCsvExporter::class)->download(
                $this->exportFilename(),
                $this->exportHeaders(),
                $this->exportRows(),
            ));
    }
}
