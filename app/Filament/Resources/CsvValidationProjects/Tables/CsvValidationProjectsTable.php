<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\Tables;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class CsvValidationProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_number')->searchable()->sortable(),
                TextColumn::make('system_name')->searchable()->description(fn ($record): string => $record->system_identifier),
                TextColumn::make('system_version')->placeholder('Not set'),
                TextColumn::make('gxp_criticality')->badge()->formatStateUsing(fn ($state): string => str($state->value)->title()->toString()),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state): string => str($state->value)->replace('_', ' ')->title()->toString()),
                IconColumn::make('is_gxp')->boolean()->label('GxP'),
                TextColumn::make('qualityOwner.name')->label('QA owner')->placeholder('Not assigned'),
                TextColumn::make('next_periodic_review_date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::enumOptions(CsvValidationProjectStatus::cases())),
                SelectFilter::make('gxp_criticality')->options(self::enumOptions(CsvCriticality::cases())),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<string, string>
     */
    private static function enumOptions(array $cases): array
    {
        return collect($cases)->mapWithKeys(
            fn (\BackedEnum $case): array => [$case->value => str($case->value)->replace('_', ' ')->title()->toString()],
        )->all();
    }
}
