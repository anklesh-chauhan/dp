<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentCalibrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Equipment calibration')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('calibration_number')->label('Calibration Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('result')->badge(),
                        TextEntry::make('equipmentAsset.asset_number')->label('Equipment Asset'),
                        TextEntry::make('due_at')->dateTime()->placeholder('—'),
                        TextEntry::make('performed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('next_due_at')->dateTime()->placeholder('—'),
                        TextEntry::make('performer.name')->label('Performed By')->placeholder('—'),
                        TextEntry::make('verifier.name')->label('Verified By')->placeholder('—'),
                        TextEntry::make('certificate_reference')->placeholder('—'),
                        TextEntry::make('deviation.deviation_number')->label('Linked Deviation')->placeholder('—'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
