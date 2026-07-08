<?php

declare(strict_types=1);

namespace App\Filament\Resources\NumberSeries\Schemas;

use App\Models\NumberSeriesSetting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NumberSeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        $settings = NumberSeriesSetting::current();

        return $schema
            ->components([
                Section::make('Document Type')
                    ->icon(Heroicon::DocumentText)
                    ->schema([
                        Placeholder::make('document_type')
                            ->label('Document type')
                            ->content(fn ($record) => "{$record->documentType->name} ({$record->documentType->code})"),
                    ]),
                Section::make('Number Format')
                    ->icon(Heroicon::Hashtag)
                    ->description('Leave fields empty to inherit the default settings.')
                    ->schema([
                        TextInput::make('prefix_pattern')
                            ->label('Prefix pattern')
                            ->placeholder($settings->default_prefix_pattern)
                            ->helperText('Placeholders: {type}, {department}')
                            ->maxLength(255),
                        TextInput::make('padding_length')
                            ->label('Padding length')
                            ->placeholder((string) $settings->default_padding_length)
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10),
                        TextInput::make('suffix')
                            ->label('Suffix')
                            ->placeholder($settings->default_suffix)
                            ->maxLength(50),
                    ])
                    ->columns(3),
            ]);
    }
}
