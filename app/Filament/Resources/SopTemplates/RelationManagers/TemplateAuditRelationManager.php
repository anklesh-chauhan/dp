<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TemplateAuditRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit Log';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->searchable(),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('old_values')
                    ->label('Previous')
                    ->formatStateUsing(function (mixed $state): string {
                        if (! is_array($state)) {
                            return (string) $state;
                        }

                        return collect($state)
                            ->map(fn ($value, $key) => "{$key}: {$value}")
                            ->implode("\n");
                    })
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('new_values')
                    ->label('Changes')
                    ->formatStateUsing(function (mixed $state): string {
                        if (! is_array($state)) {
                            return (string) $state;
                        }

                        return collect($state)
                            ->map(fn ($value, $key) => "{$key}: {$value}")
                            ->implode("\n");
                    })
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
