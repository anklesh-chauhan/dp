<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentCalibrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Equipment calibration')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('equipment_asset_id')
                            ->relationship('equipmentAsset', 'asset_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        DateTimePicker::make('due_at')
                            ->required()
                            ->native(false),
                    ]),
                    TextInput::make('certificate_reference')->maxLength(255)->live(onBlur: true),
                    Textarea::make('notes')->rows(4)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
