<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications\Tables;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Enums\EquipmentQualificationType;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EquipmentQualificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('qualification_number')->label('Number')->searchable()->sortable(),
                TextColumn::make('equipmentAsset.asset_number')->label('Asset')->searchable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('protocol_title')->limit(40)->searchable(),
                TextColumn::make('started_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EquipmentQualificationStatus::class),
                SelectFilter::make('type')->options(EquipmentQualificationType::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
