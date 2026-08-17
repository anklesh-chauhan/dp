<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews\Tables;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ProductQualityReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('review_number')->label('Review Number')->searchable()->sortable(),
                TextColumn::make('product_name')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('period_end_at')->date()->sortable(),
                TextColumn::make('closed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ProductQualityReviewStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
