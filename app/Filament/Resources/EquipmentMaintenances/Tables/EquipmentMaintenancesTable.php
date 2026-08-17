<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances\Tables;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EquipmentMaintenancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('work_order_number')->label('Work Order')->searchable()->sortable(),
                TextColumn::make('equipmentAsset.asset_number')->label('Asset')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('description')->limit(40),
                TextColumn::make('due_at')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('completed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EquipmentMaintenanceStatus::class),
            ])
            ->defaultSort('due_at')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
