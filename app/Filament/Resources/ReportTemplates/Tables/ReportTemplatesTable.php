<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReportTemplates\Tables;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ReportTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('layout_key')->label('Layout Key')->copyable()->searchable(),
                TextColumn::make('scope')
                    ->badge()
                    ->formatStateUsing(fn (ReportScope $state): string => $state->label()),
                TextColumn::make('format')
                    ->badge()
                    ->formatStateUsing(fn (ReportFormat $state): string => $state->label()),
                TextColumn::make('fields')
                    ->label('Enabled Fields')
                    ->state(fn ($record): int => count($record->enabledFieldKeys())),
                IconColumn::make('is_active')->boolean(),
                IconColumn::make('is_system')->boolean(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->options(collect(ReportScope::cases())->mapWithKeys(
                        fn (ReportScope $scope): array => [$scope->value => $scope->label()],
                    )),
                SelectFilter::make('format')
                    ->options(collect(ReportFormat::cases())->mapWithKeys(
                        fn (ReportFormat $format): array => [$format->value => $format->label()],
                    )),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
