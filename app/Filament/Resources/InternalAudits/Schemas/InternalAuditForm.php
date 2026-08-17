<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits\Schemas;

use App\Domain\QMS\Enums\InternalAuditType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class InternalAuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Internal Audit')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Select::make('type')->options(InternalAuditType::class)->required()->live(),
                    Textarea::make('scope')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('objectives')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('criteria')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Responsibility & schedule')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('lead_auditor_id')
                            ->relationship('leadAuditor', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                    Grid::make(3)->schema([
                        DatePicker::make('scheduled_start_at')->live(),
                        DatePicker::make('scheduled_end_at')->live(),
                        DatePicker::make('follow_up_due_at')->live(),
                    ]),
                    DateTimePicker::make('report_issued_at')->live(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
