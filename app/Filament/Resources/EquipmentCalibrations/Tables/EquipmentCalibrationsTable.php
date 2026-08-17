<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations\Tables;

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EquipmentCalibrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('calibration_number')->label('Number')->searchable()->sortable(),
                TextColumn::make('equipmentAsset.asset_number')->label('Asset')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('result')->badge()->sortable(),
                TextColumn::make('due_at')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('performed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EquipmentCalibrationStatus::class),
                SelectFilter::make('result')->options(EquipmentCalibrationResult::class),
            ])
            ->defaultSort('due_at')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
