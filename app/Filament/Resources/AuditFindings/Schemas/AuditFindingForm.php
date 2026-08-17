<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings\Schemas;

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingSeverity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class AuditFindingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Audit Finding')
                ->schema([
                    Select::make('internal_audit_id')
                        ->relationship('internalAudit', 'audit_number')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('objective_evidence')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('severity')->options(AuditFindingSeverity::class)->required()->live(),
                        Select::make('classification')->options(AuditFindingClassification::class)->required()->live(),
                        TextInput::make('clause_reference')->maxLength(255)->live(onBlur: true),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Responsibility')
                ->schema([
                    Grid::make(3)->schema([
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
                        DatePicker::make('response_due_at')->live(),
                    ]),
                    DateTimePicker::make('identified_at')->required()->live(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
