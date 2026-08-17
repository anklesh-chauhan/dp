<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class LaboratoryOosEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laboratory event')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('event_number')->label('Event Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('test_name'),
                        TextEntry::make('method_reference')->placeholder('—'),
                        TextEntry::make('sample_id')->placeholder('—'),
                        TextEntry::make('batch_number')->placeholder('—'),
                        TextEntry::make('product_name')->placeholder('—'),
                        TextEntry::make('specification_limit')->placeholder('—'),
                        TextEntry::make('observed_result')->placeholder('—'),
                        TextEntry::make('unit')->placeholder('—'),
                        TextEntry::make('department.name')->label('Department')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('analyst.name')->label('Analyst')->placeholder('—'),
                        TextEntry::make('creator.name')->label('Created By')->placeholder('—'),
                        TextEntry::make('phase_one_outcome')->badge()->placeholder('—'),
                        TextEntry::make('phase_two_outcome')->badge()->placeholder('—'),
                        TextEntry::make('phase_one_notes')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('phase_two_notes')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('hypothesis')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('invalidation_justification')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('investigation.investigation_number')->label('Investigation')->placeholder('—'),
                        TextEntry::make('deviation.deviation_number')->label('Deviation')->placeholder('—'),
                        TextEntry::make('started_at')->dateTime()->placeholder('—'),
                        TextEntry::make('phase_one_completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('phase_two_completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
