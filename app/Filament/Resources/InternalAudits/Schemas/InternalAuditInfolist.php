<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class InternalAuditInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Internal Audit')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('audit_number')->label('Audit Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('scope')->columnSpanFull(),
                        TextEntry::make('objectives')->columnSpanFull(),
                        TextEntry::make('criteria')->columnSpanFull(),
                        TextEntry::make('department.name')->label('Department')->placeholder('—'),
                        TextEntry::make('leadAuditor.name')->label('Lead Auditor')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('scheduled_start_at')->date()->placeholder('—'),
                        TextEntry::make('scheduled_end_at')->date()->placeholder('—'),
                        TextEntry::make('started_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('report_issued_at')->dateTime()->placeholder('—'),
                        TextEntry::make('follow_up_due_at')->date()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
