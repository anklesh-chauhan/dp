<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DeviationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Deviation')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('deviation_number')->label('Deviation Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('severity')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('immediate_actions')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('department.name')->label('Department'),
                        TextEntry::make('reporter.name')->label('Reported By')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Lifecycle')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('occurred_at')->dateTime(),
                        TextEntry::make('discovered_at')->dateTime(),
                        TextEntry::make('investigation_due_at')->date()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
