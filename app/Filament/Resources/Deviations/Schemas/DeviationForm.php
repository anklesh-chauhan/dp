<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Schemas;

use App\Domain\QMS\Enums\DeviationSeverity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DeviationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Quality Event')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(5)->columnSpanFull(),
                    Textarea::make('immediate_actions')->rows(4)->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('severity')
                            ->options(DeviationSeverity::class)
                            ->required(),
                        DateTimePicker::make('occurred_at')->required(),
                        DateTimePicker::make('discovered_at')->required(),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Responsibility')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('investigation_due_at'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
