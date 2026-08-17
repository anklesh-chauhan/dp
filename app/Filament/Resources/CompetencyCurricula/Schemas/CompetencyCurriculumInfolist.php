<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CompetencyCurriculumInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Curriculum')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('code')->copyable(),
                        TextEntry::make('name'),
                        IconEntry::make('is_active')->boolean()->label('Active'),
                        TextEntry::make('role_name')->placeholder('—'),
                        TextEntry::make('sopRole.name')->label('SOP role')->placeholder('—'),
                        TextEntry::make('requalification_months')->placeholder('—'),
                        TextEntry::make('gate_key')->placeholder('—'),
                        TextEntry::make('creator.name')->label('Created by')->placeholder('—'),
                        TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
