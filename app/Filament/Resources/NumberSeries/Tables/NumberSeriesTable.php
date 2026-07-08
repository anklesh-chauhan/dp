<?php

declare(strict_types=1);

namespace App\Filament\Resources\NumberSeries\Tables;

use App\Models\NumberSeries;
use App\Models\NumberSeriesSetting;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NumberSeriesTable
{
    public static function configure(Table $table): Table
    {
        $settings = NumberSeriesSetting::current();

        return $table
            ->columns([
                TextColumn::make('documentType.name')
                    ->label('Document type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('documentType.code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('prefix_pattern')
                    ->label('Prefix pattern')
                    ->placeholder($settings->default_prefix_pattern)
                    ->state(fn (NumberSeries $record): string => $record->effectivePrefixPattern($settings)),
                TextColumn::make('padding_length')
                    ->label('Padding')
                    ->placeholder((string) $settings->default_padding_length)
                    ->state(fn (NumberSeries $record): int => $record->effectivePaddingLength($settings)),
                TextColumn::make('suffix')
                    ->label('Suffix')
                    ->placeholder($settings->default_suffix !== '' ? $settings->default_suffix : '—')
                    ->state(fn (NumberSeries $record): string => $record->effectiveSuffix($settings) ?: '—'),
                IconColumn::make('has_custom_configuration')
                    ->label('Custom')
                    ->boolean()
                    ->state(fn (NumberSeries $record): bool => $record->hasCustomConfiguration()),
            ])
            ->defaultSort('documentType.code')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
