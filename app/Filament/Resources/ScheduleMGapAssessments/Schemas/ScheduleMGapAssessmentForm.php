<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ScheduleMGapAssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Schedule M Gap Assessment')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        TextInput::make('site_name')->required()->maxLength(255)->live(onBlur: true),
                        TextInput::make('period_label')->maxLength(255)->live(onBlur: true),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
