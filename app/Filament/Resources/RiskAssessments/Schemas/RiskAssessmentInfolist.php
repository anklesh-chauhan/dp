<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class RiskAssessmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Risk Assessment')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('risk_number')->label('Risk Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('scope')->columnSpanFull(),
                        TextEntry::make('hazard')->columnSpanFull(),
                        TextEntry::make('potential_harm')->columnSpanFull(),
                        TextEntry::make('existing_controls')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('department.name')->label('Department')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('approver.name')->label('Approver')->placeholder('—'),
                        TextEntry::make('initial_severity'),
                        TextEntry::make('initial_probability'),
                        TextEntry::make('initial_detectability'),
                        TextEntry::make('mitigation_plan')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('mitigation_due_at')->date()->placeholder('—'),
                        TextEntry::make('residual_severity')->placeholder('—'),
                        TextEntry::make('residual_probability')->placeholder('—'),
                        TextEntry::make('residual_detectability')->placeholder('—'),
                        TextEntry::make('review_due_at')->date()->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
