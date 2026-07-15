<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiExecutions\Pages;

use App\Filament\Resources\AiExecutions\AiExecutionResource;
use App\Filament\Resources\AiExecutions\Widgets\AiExecutionOverview;
use App\Filament\Resources\AiExecutions\Widgets\AiProviderPerformanceTable;
use App\Services\AI\Enums\AiObservabilityTimeWindow;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ListRecords;

final class ListAiExecutions extends ListRecords
{
    use HasFiltersAction;

    protected static string $resource = AiExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Time Window')
                ->schema([
                    Select::make('time_window')
                        ->label('Time Window')
                        ->options(AiObservabilityTimeWindow::options())
                        ->default(
                            AiObservabilityTimeWindow::LAST_30_DAYS->value,
                        ),
                ]),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AiExecutionOverview::class,
            AiProviderPerformanceTable::class,
        ];
    }
}
