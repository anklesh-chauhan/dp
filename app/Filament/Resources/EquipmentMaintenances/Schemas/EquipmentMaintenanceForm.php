<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentMaintenanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Preventive maintenance work order')
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
                    Textarea::make('description')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('notes')->rows(3)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
