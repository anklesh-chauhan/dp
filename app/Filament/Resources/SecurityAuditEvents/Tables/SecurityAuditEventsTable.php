<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityAuditEvents\Tables;

use App\Domain\Shared\Enums\SecurityAuditEventType;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class SecurityAuditEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => self::eventLabel($state))
                    ->color(fn (mixed $state): string => self::eventColor($state))
                    ->searchable(),
                TextColumn::make('actor_name')
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('actor_email')
                    ->label('Actor email')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('subject_email')
                    ->label('Subject')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('reason')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('event_uuid')
                    ->label('Event ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Event')
                    ->options(
                        collect(SecurityAuditEventType::cases())
                            ->mapWithKeys(fn (SecurityAuditEventType $type): array => [
                                $type->value => $type->label(),
                            ])
                            ->all(),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->emptyStateHeading('No security audit events yet')
            ->emptyStateDescription('Login, lockout, password, role, signature, and backup actions will appear here.')
            ->emptyStateIcon(Heroicon::OutlinedShieldCheck);
    }

    private static function eventLabel(mixed $state): string
    {
        return $state instanceof SecurityAuditEventType
            ? $state->label()
            : (string) $state;
    }

    private static function eventColor(mixed $state): string
    {
        return $state instanceof SecurityAuditEventType
            ? $state->color()
            : 'gray';
    }
}
