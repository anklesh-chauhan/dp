<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeGuides\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class KnowledgeGuideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Guide Details')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, ?string $old, $livewire): void {
                                        if ($livewire instanceof CreateRecord) {
                                            $set('slug', Str::slug($state ?? ''));
                                        }
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Used in URLs. Auto-generated from the title on create.'),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),
                                Toggle::make('is_published')
                                    ->default(true)
                                    ->helperText('Unpublished guides are only visible to users who can manage guides.'),
                                Textarea::make('summary')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->columnSpanFull()
                                    ->helperText('Short description shown in the knowledge library list.'),

                                ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Content')
                    ->schema([
                        Textarea::make('content')
                            ->required()
                            ->rows(24)
                            ->columnSpanFull()
                            ->helperText('Markdown supported. Use headings, lists, and tables for structured guides.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
