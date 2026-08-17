<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\Tables;

use App\Domain\QMS\Enums\EquipmentAssetCategory;
use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use App\Domain\QMS\Enums\EquipmentAssetStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EquipmentAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset_number')->label('Asset Number')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('criticality')->badge(),
                IconColumn::make('gxp_impact')->boolean()->toggleable(),
                TextColumn::make('location')->placeholder('—')->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(EquipmentAssetStatus::class),
                SelectFilter::make('category')->options(EquipmentAssetCategory::class),
                SelectFilter::make('criticality')->options(EquipmentAssetCriticality::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
