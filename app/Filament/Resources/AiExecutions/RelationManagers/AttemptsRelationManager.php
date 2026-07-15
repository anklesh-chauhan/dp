<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiExecutions\RelationManagers;

use App\Models\AiExecutionAttempt;
use App\Services\AI\Enums\AiExecutionStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Provider Attempts';

    protected static ?string $recordTitleAttribute = 'provider';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sequence')
                    ->label('#')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('provider')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (mixed $state): string => self::formatEnumState($state),
                    )
                    ->color(
                        fn (mixed $state): string => self::statusColor($state),
                    )
                    ->sortable(),

                TextColumn::make('input_tokens')
                    ->label('Input')
                    ->numeric()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('output_tokens')
                    ->label('Output')
                    ->numeric()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(
                        fn (?int $state): string => self::formatDuration($state),
                    )
                    ->sortable(),

                TextColumn::make('exception_class')
                    ->label('Exception')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->placeholder('—')
                    ->limit(80)
                    ->tooltip(
                        fn (AiExecutionAttempt $record): ?string => $record->error_message,
                    )
                    ->wrap(),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('failed_at')
                    ->label('Failed')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(
                        collect(AiExecutionStatus::cases())
                            ->mapWithKeys(
                                fn (AiExecutionStatus $status): array => [
                                    $status->value => self::formatEnumState($status),
                                ],
                            )
                            ->all(),
                    ),

                SelectFilter::make('provider')
                    ->options(
                        fn (): array => AiExecutionAttempt::query()
                            ->whereNotNull('provider')
                            ->distinct()
                            ->orderBy('provider')
                            ->pluck('provider', 'provider')
                            ->all(),
                    ),
            ])
            ->defaultSort('sequence');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    private static function formatEnumState(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return str($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private static function statusColor(mixed $state): string
    {
        $value = $state instanceof AiExecutionStatus
            ? $state
            : AiExecutionStatus::tryFrom((string) $state);

        return match ($value) {
            AiExecutionStatus::RUNNING => 'warning',
            AiExecutionStatus::SUCCEEDED => 'success',
            AiExecutionStatus::FAILED => 'danger',
            default => 'gray',
        };
    }

    private static function formatDuration(?int $durationMs): string
    {
        if ($durationMs === null) {
            return '—';
        }

        if ($durationMs < 1_000) {
            return "{$durationMs} ms";
        }

        return number_format(
            $durationMs / 1_000,
            2,
        ).' s';
    }
}
