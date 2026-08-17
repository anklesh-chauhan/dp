<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments\Tables;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class RiskAssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('risk_number')->label('Risk Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('mitigation_due_at')->date()->placeholder('—')->sortable(),
                TextColumn::make('review_due_at')->date()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(RiskAssessmentStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
