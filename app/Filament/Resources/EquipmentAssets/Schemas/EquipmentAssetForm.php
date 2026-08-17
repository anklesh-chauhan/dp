<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\Schemas;

use App\Domain\QMS\Enums\EquipmentAssetCategory;
use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EquipmentAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Equipment asset')
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        TextInput::make('asset_tag')->maxLength(255)->live(onBlur: true),
                        TextInput::make('location')->maxLength(255)->live(onBlur: true),
                        Select::make('category')->options(EquipmentAssetCategory::class)->required()->live(),
                        Select::make('criticality')->options(EquipmentAssetCriticality::class)->required()->live(),
                        Toggle::make('gxp_impact')->inline(false)->live(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('manufacturer')->maxLength(255)->live(onBlur: true),
                        TextInput::make('model')->maxLength(255)->live(onBlur: true),
                        TextInput::make('serial_number')->maxLength(255)->live(onBlur: true),
                    ]),
                    Grid::make(2)->schema([
                        DateTimePicker::make('installed_at')->native(false)->live(),
                        DateTimePicker::make('commissioned_at')->native(false)->live(),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Ownership')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('validation_master_plan_id')
                            ->relationship('validationMasterPlan', 'vmp_number')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
