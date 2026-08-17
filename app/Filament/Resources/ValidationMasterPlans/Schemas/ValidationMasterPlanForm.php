<?php

declare(strict_types=1);

namespace App\Filament\Resources\ValidationMasterPlans\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ValidationMasterPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Validation master plan')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('scope')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(2)->schema([
                        DateTimePicker::make('period_start_at')->native(false)->live(),
                        DateTimePicker::make('period_end_at')->native(false)->live(),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Ownership & document')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('controlled_document_id')
                            ->relationship('controlledDocument', 'document_number')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
