<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AuditFindingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Audit Finding')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('finding_number')->label('Finding Number')->copyable(),
                        TextEntry::make('disposition')->badge(),
                        TextEntry::make('internalAudit.audit_number')->label('Internal Audit'),
                        TextEntry::make('severity')->badge(),
                        TextEntry::make('classification')->badge(),
                        TextEntry::make('clause_reference')->placeholder('—'),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('objective_evidence')->columnSpanFull(),
                        TextEntry::make('department.name')->label('Department')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('identifier.name')->label('Identified By')->placeholder('—'),
                        TextEntry::make('identified_at')->dateTime()->placeholder('—'),
                        TextEntry::make('response_due_at')->date()->placeholder('—'),
                        TextEntry::make('response')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('verifier.name')->label('Verified By')->placeholder('—'),
                        TextEntry::make('verification_notes')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('verified_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
