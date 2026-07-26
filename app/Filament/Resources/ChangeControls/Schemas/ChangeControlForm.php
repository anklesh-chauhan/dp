<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ChangeControlForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Proposed Change')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                        Textarea::make('description')->required()->rows(5)->columnSpanFull(),
                        Textarea::make('rationale')->required()->rows(4)->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Responsibility and Planning')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('department_id')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('owner_id')
                                ->relationship('owner', 'name')
                                ->searchable()
                                ->preload(),
                            DatePicker::make('implementation_due_at'),
                            DatePicker::make('effectiveness_due_at'),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
