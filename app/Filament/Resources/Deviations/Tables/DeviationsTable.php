<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Tables;

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class DeviationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deviation_number')->label('Deviation Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('severity')->badge()->sortable(),
                TextColumn::make('department.name')->searchable()->sortable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('investigation_due_at')->date()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(DeviationStatus::class),
                SelectFilter::make('severity')->options(DeviationSeverity::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
