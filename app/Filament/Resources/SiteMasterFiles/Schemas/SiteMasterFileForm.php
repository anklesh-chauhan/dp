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
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->helperText('Use the official site name and document purpose, for example “Site Master File — Main Manufacturing Facility”.')
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->helperText('Select the person accountable for maintaining this Site Master File.'),
                        TextInput::make('version')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live()
                            ->helperText('Enter the controlled document version number. Start new Site Master Files at version 1.'),
                    ]),
                    Textarea::make('sections.site_information.content')
                        ->label('Site information')
                        ->rows(4)
                        ->live(onBlur: true)
                        ->helperText('Summarize the site, licensed activities, products handled, contact details, and any nearby operations.')
                        ->columnSpanFull(),
                    Textarea::make('sections.pharmaceutical_quality_system.content')
                        ->label('Pharmaceutical quality system')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Describe the quality system, management responsibilities, release arrangements, supplier controls, and quality risk management.')
                        ->columnSpanFull(),
                    Textarea::make('sections.personnel.content')
                        ->label('Personnel')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Outline the organizational structure, key responsible roles, staffing levels, training, and health or hygiene requirements.')
                        ->columnSpanFull(),
                    Textarea::make('sections.premises_and_equipment.content')
                        ->label('Premises and equipment')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Describe facility layout, material and personnel flows, utilities, major equipment, maintenance, and cleaning arrangements.')
                        ->columnSpanFull(),
                    Textarea::make('sections.documentation.content')
                        ->label('Documentation')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Explain document control, record retention, electronic systems, and preparation, review, approval, and distribution practices.')
                        ->columnSpanFull(),
                    Textarea::make('sections.production.content')
                        ->label('Production')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Summarize manufacturing operations, process validation, material handling, reprocessing policies, and production controls.')
                        ->columnSpanFull(),
                    Textarea::make('sections.quality_control.content')
                        ->label('Quality control')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Describe sampling, testing, specifications, laboratory controls, stability activities, and batch disposition support.')
                        ->columnSpanFull(),
                    Textarea::make('sections.distribution_complaints_recalls.content')
                        ->label('Distribution, complaints and recalls')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Explain storage and distribution controls, traceability, complaint handling, defect reporting, and recall arrangements.')
                        ->columnSpanFull(),
                    Textarea::make('sections.self_inspection.content')
                        ->label('Self-inspection')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Describe the self-inspection programme, audit frequency, responsibilities, reporting, and corrective action follow-up.')
                        ->columnSpanFull(),
                    Textarea::make('sections.contract_activities.content')
                        ->label('Contract activities')
                        ->rows(3)
                        ->live(onBlur: true)
                        ->helperText('Identify outsourced GMP activities and summarize contractor qualification, technical agreements, oversight, and review.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
