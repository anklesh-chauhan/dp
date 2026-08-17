<?php

declare(strict_types=1);

namespace App\Filament\Resources\ValidationMasterPlans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ValidationMasterPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Validation master plan')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('vmp_number')->label('VMP Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('scope')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('period_start_at')->dateTime()->placeholder('—'),
                        TextEntry::make('period_end_at')->dateTime()->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('creator.name')->label('Created By')->placeholder('—'),
                        TextEntry::make('approver.name')->label('Approved By')->placeholder('—'),
                        TextEntry::make('controlledDocument.document_number')->label('Controlled Document')->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                        TextEntry::make('retired_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
