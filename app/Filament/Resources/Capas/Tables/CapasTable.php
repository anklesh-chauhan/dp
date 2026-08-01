<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Tables;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\CapaType;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class CapasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('capa_number')->label('CAPA Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('deviation.deviation_number')->label('Deviation')->searchable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('due_at')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(CapaStatus::class),
                SelectFilter::make('type')->options(CapaType::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }
}
