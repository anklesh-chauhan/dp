<?php

declare(strict_types=1);

namespace App\Filament\Resources\Investigations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class InvestigationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Investigation')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('deviation_id')
                            ->relationship('deviation', 'deviation_number')
                            ->searchable()
                            ->preload()
                            ->required()->live(),
                        Select::make('lead_id')
                            ->relationship('lead', 'name')
                            ->searchable()
                            ->preload()->live(),
                        DatePicker::make('due_at')->live(),
                    ]),
                    Textarea::make('methodology')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('root_cause')->rows(5)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('conclusion')->rows(5)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
