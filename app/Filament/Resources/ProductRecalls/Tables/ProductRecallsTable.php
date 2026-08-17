<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductRecalls\Tables;

use App\Domain\QMS\Enums\ProductRecallStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ProductRecallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recall_number')->label('Recall Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('classification')->badge()->toggleable(),
                TextColumn::make('product_name')->searchable()->toggleable(),
                IconColumn::make('is_mock')->boolean()->label('Mock'),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('closed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ProductRecallStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
