<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityAuditEvents\Schemas;

use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Models\SecurityAuditEvent;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SecurityAuditEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('event_type')
                                    ->label('Event')
                                    ->badge()
                                    ->formatStateUsing(fn (mixed $state): string => $state instanceof SecurityAuditEventType
                                        ? $state->label()
                                        : (string) $state)
                                    ->color(fn (mixed $state): string => $state instanceof SecurityAuditEventType
                                        ? $state->color()
                                        : 'gray'),
                                TextEntry::make('occurred_at')
                                    ->label('When')
                                    ->dateTime(),
                                TextEntry::make('event_uuid')
                                    ->label('Event ID')
                                    ->copyable(),
                            ]),
                        TextEntry::make('reason')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('People')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('actor_name')
                                    ->label('Actor')
                                    ->placeholder('System'),
                                TextEntry::make('actor_email')
                                    ->label('Actor email')
                                    ->placeholder('—'),
                                TextEntry::make('subjectUser.name')
                                    ->label('Subject user')
                                    ->placeholder('—'),
                                TextEntry::make('subject_email')
                                    ->label('Subject email')
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Technical details')
                    ->schema([
                        TextEntry::make('ip_address')
                            ->label('IP address')
                            ->placeholder('—'),
                        TextEntry::make('user_agent')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        KeyValueEntry::make('context')
                            ->label('Context')
                            ->state(fn (SecurityAuditEvent $record): array => self::contextEntries($record))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function contextEntries(SecurityAuditEvent $record): array
    {
        $context = $record->context;

        if (! is_array($context) || $context === []) {
            return [];
        }

        return collect($context)
            ->mapWithKeys(function (mixed $value, mixed $key): array {
                $encoded = is_scalar($value) || $value === null
                    ? (string) ($value ?? '')
                    : (json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

                return [(string) $key => $encoded];
            })
            ->all();
    }
}
