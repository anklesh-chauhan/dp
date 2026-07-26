<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CapaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('CAPA')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('capa_number')->label('CAPA Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('deviation.deviation_number')->label('Deviation'),
                        TextEntry::make('investigation.investigation_number')->label('Investigation')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('action_plan')->columnSpanFull(),
                        TextEntry::make('effectiveness_result')->columnSpanFull()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Milestones')
                ->schema([
                    Grid::make(5)->schema([
                        TextEntry::make('due_at')->date(),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('effectiveness_due_at')->date()->placeholder('—'),
                        TextEntry::make('effectiveness_verified_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
