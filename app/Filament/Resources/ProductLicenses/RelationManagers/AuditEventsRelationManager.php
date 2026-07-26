<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductLicenses\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AuditEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditEvents';

    protected static ?string $title = 'Audit History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Occurred At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => self::formatState($state)),
                TextColumn::make('from_state')
                    ->label('From')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (mixed $state): string => self::formatState($state)),
                TextColumn::make('to_state')
                    ->label('To')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => self::formatState($state)),
                TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    private static function formatState(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return str($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
