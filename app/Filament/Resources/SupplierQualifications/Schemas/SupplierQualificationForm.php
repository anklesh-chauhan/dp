<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierQualifications\Schemas;

use App\Domain\QMS\Enums\SupplierCategory;
use App\Domain\QMS\Enums\SupplierRiskLevel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SupplierQualificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Supplier')
                ->schema([
                    TextInput::make('legal_name')->required()->maxLength(255)->live(onBlur: true),
                    TextInput::make('site_name')->maxLength(255)->live(onBlur: true),
                    Grid::make(3)->schema([
                        Select::make('category')->options(SupplierCategory::class)->required()->live(),
                        Select::make('risk_level')->options(SupplierRiskLevel::class)->required()->live(),
                        TextInput::make('country_code')->maxLength(2)->live(onBlur: true),
                    ]),
                    Textarea::make('material_service_scope')->required()->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('site_address')->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        TextInput::make('contact_name')->maxLength(255)->live(onBlur: true),
                        TextInput::make('contact_email')->email()->maxLength(255)->live(onBlur: true),
                        TextInput::make('contact_phone')->maxLength(255)->live(onBlur: true),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Qualification ownership')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                    Textarea::make('qualification_rationale')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        DatePicker::make('audit_due_at')->live(),
                        DateTimePicker::make('audit_completed_at')->live(),
                        DatePicker::make('qualification_expires_at')->live(),
                    ]),
                    DatePicker::make('next_review_at')->live(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
