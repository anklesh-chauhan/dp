<?php

declare(strict_types=1);

namespace App\Filament\Resources\Investigations\Tables;

use App\Domain\QMS\Enums\InvestigationStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class InvestigationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('investigation_number')->label('Investigation Number')->searchable()->sortable(),
                TextColumn::make('deviation.deviation_number')->label('Deviation')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('lead.name')->label('Lead')->placeholder('—'),
                TextColumn::make('due_at')->date()->placeholder('—')->sortable(),
                TextColumn::make('completed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(InvestigationStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }
}
