<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentAssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Equipment asset')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('asset_number')->label('Asset Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('name')->columnSpanFull(),
                        TextEntry::make('asset_tag')->placeholder('—'),
                        TextEntry::make('location')->placeholder('—'),
                        TextEntry::make('category')->badge(),
                        TextEntry::make('criticality')->badge(),
                        IconEntry::make('gxp_impact')->boolean(),
                        TextEntry::make('manufacturer')->placeholder('—'),
                        TextEntry::make('model')->placeholder('—'),
                        TextEntry::make('serial_number')->placeholder('—'),
                        TextEntry::make('department.name')->label('Department')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('validationMasterPlan.vmp_number')->label('VMP')->placeholder('—'),
                        TextEntry::make('installed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('commissioned_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
