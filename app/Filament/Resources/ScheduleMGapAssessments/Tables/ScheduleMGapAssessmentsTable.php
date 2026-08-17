<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\Tables;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ScheduleMGapAssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assessment_number')->label('Assessment Number')->searchable()->sortable(),
                TextColumn::make('site_name')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('period_label')->placeholder('—'),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('closed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ScheduleMGapAssessmentStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
