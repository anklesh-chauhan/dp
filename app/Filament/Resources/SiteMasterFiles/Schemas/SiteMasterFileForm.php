<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SiteMasterFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Site Master File')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(2)->schema([
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        TextInput::make('version')->numeric()->minValue(1)->default(1)->required()->live(),
                    ]),
                    Textarea::make('sections.site_information.content')
                        ->label('Site information')
                        ->rows(4)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.pharmaceutical_quality_system.content')
                        ->label('Pharmaceutical quality system')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.personnel.content')
                        ->label('Personnel')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.premises_and_equipment.content')
                        ->label('Premises and equipment')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.documentation.content')
                        ->label('Documentation')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.production.content')
                        ->label('Production')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.quality_control.content')
                        ->label('Quality control')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.distribution_complaints_recalls.content')
                        ->label('Distribution, complaints and recalls')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.self_inspection.content')
                        ->label('Self-inspection')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('sections.contract_activities.content')
                        ->label('Contract activities')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
