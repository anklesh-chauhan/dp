<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('designation.name')
                    ->label('Designation')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('deactivated_at')
                    ->label('Active')
                    ->boolean()
                    ->state(fn ($record): bool => $record->deactivated_at === null),
                TextColumn::make('locked_at')
                    ->label('Locked')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('last_login_at')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }
}
