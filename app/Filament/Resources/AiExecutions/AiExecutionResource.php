<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiExecutions;

use App\Enums\ProductModule;
use App\Filament\Resources\AiExecutions\Pages\ListAiExecutions;
use App\Filament\Resources\AiExecutions\Pages\ViewAiExecution;
use App\Filament\Resources\AiExecutions\RelationManagers\AttemptsRelationManager;
use App\Models\AiExecution;
use App\Services\AI\Enums\AiExecutionStatus;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\LLMCapability;
use App\Support\Modules\ModuleManager;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class AiExecutionResource extends Resource
{
    protected static ?string $model = AiExecution::class;

    protected static string|UnitEnum|null $navigationGroup = 'AI Management';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'ulid';

    protected static ?string $modelLabel = 'AI Execution';

    protected static ?string $pluralModelLabel = 'AI Executions';

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::AI)
            && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::AI)
            && parent::shouldRegisterNavigation();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Execution Overview')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('ulid')
                                    ->label('Execution ULID')
                                    ->copyable(),

                                TextEntry::make('use_case')
                                    ->label('Use Case')
                                    ->formatStateUsing(
                                        fn (mixed $state): string => self::formatEnumState($state),
                                    ),

                                TextEntry::make('capability')
                                    ->label('Capability')
                                    ->formatStateUsing(
                                        fn (mixed $state): string => self::formatEnumState($state),
                                    ),

                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(
                                        fn (mixed $state): string => self::formatEnumState($state),
                                    )
                                    ->color(
                                        fn (mixed $state): string => self::statusColor($state),
                                    ),

                                TextEntry::make('attempt_count')
                                    ->label('Attempts')
                                    ->numeric(),

                                TextEntry::make('duration_ms')
                                    ->label('Duration')
                                    ->formatStateUsing(
                                        fn (?int $state): string => self::formatDuration($state),
                                    ),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Successful Provider')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('successful_provider')
                                    ->label('Provider')
                                    ->placeholder('—'),

                                TextEntry::make('successful_model')
                                    ->label('Model')
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Token Usage')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('input_tokens')
                                    ->label('Input Tokens')
                                    ->numeric()
                                    ->placeholder('—'),

                                TextEntry::make('output_tokens')
                                    ->label('Output Tokens')
                                    ->numeric()
                                    ->placeholder('—'),

                                TextEntry::make('total_tokens')
                                    ->label('Total Tokens')
                                    ->state(
                                        fn (AiExecution $record): ?int => self::totalTokens($record),
                                    )
                                    ->numeric()
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Lifecycle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('started_at')
                                    ->label('Started At')
                                    ->dateTime()
                                    ->placeholder('—'),

                                TextEntry::make('completed_at')
                                    ->label('Completed At')
                                    ->dateTime()
                                    ->placeholder('—'),

                                TextEntry::make('failed_at')
                                    ->label('Failed At')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ulid')
                    ->label('Execution')
                    ->searchable()
                    ->copyable()
                    ->limit(16)
                    ->tooltip(
                        fn (AiExecution $record): string => $record->ulid,
                    ),

                TextColumn::make('use_case')
                    ->label('Use Case')
                    ->formatStateUsing(
                        fn (mixed $state): string => self::formatEnumState($state),
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('capability')
                    ->formatStateUsing(
                        fn (mixed $state): string => self::formatEnumState($state),
                    )
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (mixed $state): string => self::formatEnumState($state),
                    )
                    ->color(
                        fn (mixed $state): string => self::statusColor($state),
                    )
                    ->sortable(),

                TextColumn::make('successful_provider')
                    ->label('Provider')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('successful_model')
                    ->label('Model')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('attempt_count')
                    ->label('Attempts')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('input_tokens')
                    ->label('Input')
                    ->numeric()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('output_tokens')
                    ->label('Output')
                    ->numeric()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(
                        fn (?int $state): string => self::formatDuration($state),
                    )
                    ->sortable(),

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

                SelectFilter::make('use_case')
                    ->options(
                        collect(AIUseCase::cases())
                            ->mapWithKeys(
                                fn (AIUseCase $useCase): array => [
                                    $useCase->value => self::formatEnumState($useCase),
                                ],
                            )
                            ->all(),
                    ),

                SelectFilter::make('capability')
                    ->options(
                        collect(LLMCapability::cases())
                            ->mapWithKeys(
                                fn (LLMCapability $capability): array => [
                                    $capability->value => self::formatEnumState($capability),
                                ],
                            )
                            ->all(),
                    ),

                SelectFilter::make('successful_provider')
                    ->label('Provider')
                    ->options(
                        fn (): array => AiExecution::query()
                            ->whereNotNull('successful_provider')
                            ->distinct()
                            ->orderBy('successful_provider')
                            ->pluck(
                                'successful_provider',
                                'successful_provider',
                            )
                            ->all(),
                    ),
            ])
            ->defaultSort('started_at', 'desc')
            ->recordUrl(
                fn (AiExecution $record): string => self::getUrl(
                    'view',
                    ['record' => $record],
                ),
            );
    }

    public static function getRelations(): array
    {
        return [
            AttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiExecutions::route('/'),
            'view' => ViewAiExecution::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('attempts');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
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

    private static function totalTokens(AiExecution $execution): ?int
    {
        if (
            $execution->input_tokens === null
            && $execution->output_tokens === null
        ) {
            return null;
        }

        return ($execution->input_tokens ?? 0)
            + ($execution->output_tokens ?? 0);
    }
}
