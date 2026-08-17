<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications\Schemas;

use App\Domain\QMS\Enums\EquipmentQualificationType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentQualificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Equipment qualification')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('equipment_asset_id')
                            ->relationship('equipmentAsset', 'asset_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('type')
                            ->options(EquipmentQualificationType::class)
                            ->required()
                            ->live(),
                    ]),
                    TextInput::make('protocol_title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('protocol_summary')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('acceptance_criteria')->rows(4)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
