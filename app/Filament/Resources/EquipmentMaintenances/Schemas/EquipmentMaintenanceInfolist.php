<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentMaintenanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Preventive maintenance work order')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('work_order_number')->label('Work Order Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('equipmentAsset.asset_number')->label('Equipment Asset'),
                        TextEntry::make('due_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('performer.name')->label('Performed By')->placeholder('—'),
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
