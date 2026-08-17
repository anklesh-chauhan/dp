<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class CompetencyCurriculaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('role_name')->placeholder('—')->toggleable(),
                TextColumn::make('sopRole.name')->label('SOP role')->placeholder('—')->toggleable(),
                TextColumn::make('gate_key')->placeholder('—')->toggleable(),
                TextColumn::make('requalification_months')->label('Requal. months')->placeholder('—'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->defaultSort('code')
            ->recordActions([
                ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }
}
