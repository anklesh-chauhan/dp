<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiExecutions\Widgets;

use App\Models\AiExecutionAttempt;
use App\Services\AI\Enums\AiExecutionStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class AiProviderPerformanceTable extends TableWidget
{
    protected static ?string $heading = 'Provider Performance';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getProviderPerformanceQuery())
            ->columns([
                TextColumn::make('provider')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('successful_attempts')
                    ->label('Successful')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('failed_attempts')
                    ->label('Failed')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(
                        fn (mixed $state): string => $this->formatPercentage($state),
                    )
                    ->sortable(),

                TextColumn::make('average_duration_ms')
                    ->label('Avg. Duration')
                    ->formatStateUsing(
                        fn (mixed $state): string => $this->formatDuration($state),
                    )
                    ->sortable(),

                TextColumn::make('total_input_tokens')
                    ->label('Input Tokens')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_output_tokens')
                    ->label('Output Tokens')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_tokens')
                    ->label('Total Tokens')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('total_attempts', 'desc')
            ->defaultKeySort(false)
            ->paginated(true);
    }

    /**
     * @return Builder<AiExecutionAttempt>
     */
    private function getProviderPerformanceQuery(): Builder
    {
        $succeeded = AiExecutionStatus::SUCCEEDED->value;
        $failed = AiExecutionStatus::FAILED->value;

        return AiExecutionAttempt::query()
            ->select('provider')
            ->selectRaw('MIN(id) as id')
            ->selectRaw('COUNT(*) as total_attempts')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful_attempts',
                [$succeeded],
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_attempts',
                [$failed],
            )
            ->selectRaw(
                <<<'SQL'
                    CASE
                        WHEN COUNT(*) = 0 THEN 0
                        ELSE (
                            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)
                            * 100.0
                            / COUNT(*)
                        )
                    END as success_rate
                SQL,
                [$succeeded],
            )
            ->selectRaw('AVG(duration_ms) as average_duration_ms')
            ->selectRaw('COALESCE(SUM(input_tokens), 0) as total_input_tokens')
            ->selectRaw('COALESCE(SUM(output_tokens), 0) as total_output_tokens')
            ->selectRaw(
                <<<'SQL'
                    COALESCE(SUM(input_tokens), 0)
                    + COALESCE(SUM(output_tokens), 0)
                    as total_tokens
                SQL,
            )
            ->groupBy('provider');
    }

    private function formatPercentage(mixed $percentage): string
    {
        return number_format(
            (float) $percentage,
            1,
        ).'%';
    }

    private function formatDuration(mixed $durationMs): string
    {
        if ($durationMs === null) {
            return '—';
        }

        $durationMs = (int) round((float) $durationMs);

        if ($durationMs < 1_000) {
            return "{$durationMs} ms";
        }

        return number_format(
            $durationMs / 1_000,
            2,
        ).' s';
    }
}
