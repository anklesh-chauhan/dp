<?php

declare(strict_types=1);

namespace App\Filament\Resources\Complaints\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ComplaintInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Complaint')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('complaint_number')->label('Complaint Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('source')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('received_at')->dateTime(),
                        TextEntry::make('receiver.name')->label('Received By')->placeholder('—'),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('external_reference')->placeholder('—'),
                        TextEntry::make('department.name')->label('Department')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('response_due_at')->date()->placeholder('—'),
                        TextEntry::make('product_name')->placeholder('—'),
                        TextEntry::make('batch_number')->placeholder('—'),
                        TextEntry::make('market_country_code')->placeholder('—'),
                        IconEntry::make('adverse_event_suspected')->boolean()->placeholder('—'),
                        IconEntry::make('regulatory_reportable')->boolean()->placeholder('—'),
                        TextEntry::make('regulatory_authority')->placeholder('—'),
                        TextEntry::make('regulatory_report_due_at')->date()->placeholder('—'),
                        TextEntry::make('regulatory_reported_at')->dateTime()->placeholder('—'),
                        TextEntry::make('regulatory_reference')->placeholder('—'),
                        TextEntry::make('acknowledged_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
