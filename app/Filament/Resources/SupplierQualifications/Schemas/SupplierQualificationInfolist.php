<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierQualifications\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SupplierQualificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Supplier Qualification')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('supplier_number')->label('Supplier Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('risk_level')->badge(),
                        TextEntry::make('legal_name'),
                        TextEntry::make('site_name')->placeholder('—'),
                        TextEntry::make('category')->badge(),
                        TextEntry::make('material_service_scope')->columnSpanFull(),
                        TextEntry::make('country_code')->placeholder('—'),
                        TextEntry::make('site_address')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('contact_name')->placeholder('—'),
                        TextEntry::make('contact_email')->placeholder('—'),
                        TextEntry::make('contact_phone')->placeholder('—'),
                        TextEntry::make('department.name')->label('Department')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('approver.name')->label('Approver')->placeholder('—'),
                        TextEntry::make('qualification_rationale')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('audit_due_at')->date()->placeholder('—'),
                        TextEntry::make('audit_completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('qualified_at')->dateTime()->placeholder('—'),
                        TextEntry::make('qualification_expires_at')->date()->placeholder('—'),
                        TextEntry::make('next_review_at')->date()->placeholder('—'),
                        TextEntry::make('suspended_at')->dateTime()->placeholder('—'),
                        TextEntry::make('disqualified_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
