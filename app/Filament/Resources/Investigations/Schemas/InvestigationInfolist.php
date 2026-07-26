<?php

declare(strict_types=1);

namespace App\Filament\Resources\Investigations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class InvestigationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Investigation')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('investigation_number')->label('Investigation Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('deviation.deviation_number')->label('Deviation'),
                        TextEntry::make('lead.name')->label('Lead')->placeholder('—'),
                        TextEntry::make('due_at')->date()->placeholder('—'),
                        TextEntry::make('started_at')->dateTime()->placeholder('—'),
                        TextEntry::make('methodology')->columnSpanFull(),
                        TextEntry::make('root_cause')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('conclusion')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
