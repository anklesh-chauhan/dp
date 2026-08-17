<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents\Tables;

use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Enums\LaboratoryOosType;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class LaboratoryOosEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_number')->label('Event Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('test_name')->toggleable(),
                TextColumn::make('batch_number')->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('started_at')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('closed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(LaboratoryOosStatus::class),
                SelectFilter::make('type')->options(LaboratoryOosType::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
