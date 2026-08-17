<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews\Tables;

use App\Domain\QMS\Enums\ManagementReviewStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ManagementReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('review_number')->label('Review Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('chair.name')->label('Chair')->placeholder('—'),
                TextColumn::make('scheduled_at')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('completed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ManagementReviewStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
