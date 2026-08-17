<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ScheduleMGapAssessmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Schedule M Gap Assessment')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('assessment_number')->label('Assessment Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('period_label')->placeholder('—'),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('site_name'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('creator.name')->label('Created By')->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('items_count')
                            ->label('Clause Items')
                            ->state(fn ($record): int => $record->items()->count()),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
