<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SiteMasterFileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Site Master File')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('smf_number')->label('SMF Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('version'),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('publisher.name')->label('Published By')->placeholder('—'),
                        TextEntry::make('published_at')->dateTime()->placeholder('—'),
                        TextEntry::make('sections.site_information.content')
                            ->label('Site information')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.pharmaceutical_quality_system.content')
                            ->label('Pharmaceutical quality system')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.personnel.content')
                            ->label('Personnel')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.premises_and_equipment.content')
                            ->label('Premises and equipment')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.documentation.content')
                            ->label('Documentation')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.production.content')
                            ->label('Production')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.quality_control.content')
                            ->label('Quality control')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.distribution_complaints_recalls.content')
                            ->label('Distribution, complaints and recalls')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.self_inspection.content')
                            ->label('Self-inspection')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('sections.contract_activities.content')
                            ->label('Contract activities')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
