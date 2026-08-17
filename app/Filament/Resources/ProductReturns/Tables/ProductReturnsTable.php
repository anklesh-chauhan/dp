<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductReturns\Tables;

use App\Domain\QMS\Enums\ProductReturnStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ProductReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')->label('Return Number')->searchable()->sortable(),
                TextColumn::make('product_name')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('disposition')->badge(),
                TextColumn::make('batch_number')->placeholder('—')->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('closed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ProductReturnStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
