<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingPrograms\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TrainingProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Program')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true),
                        TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                        Toggle::make('is_active')->label('Active')->inline(false),
                    ]),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
