<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentQualificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Equipment qualification')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('qualification_number')->label('Qualification Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('equipmentAsset.asset_number')->label('Equipment Asset'),
                        TextEntry::make('protocol_title')->columnSpanFull(),
                        TextEntry::make('protocol_summary')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('acceptance_criteria')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('executor.name')->label('Executed By')->placeholder('—'),
                        TextEntry::make('reviewer.name')->label('Reviewed By')->placeholder('—'),
                        TextEntry::make('deviation.deviation_number')->label('Linked Deviation')->placeholder('—'),
                        TextEntry::make('started_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
