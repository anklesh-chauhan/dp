<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments\Schemas;

use App\Domain\QMS\Enums\RiskAssessmentType;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class RiskAssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Risk Assessment')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Select::make('type')->options(RiskAssessmentType::class)->required()->live(),
                    Textarea::make('scope')->required()->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('hazard')->required()->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('potential_harm')->required()->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('existing_controls')->rows(3)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Initial scoring & ownership')
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
                        DatePicker::make('review_due_at')->live(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('initial_severity')->numeric()->minValue(1)->maxValue(5)->required()->live(),
                        TextInput::make('initial_probability')->numeric()->minValue(1)->maxValue(5)->required()->live(),
                        TextInput::make('initial_detectability')->numeric()->minValue(1)->maxValue(5)->required()->live(),
                    ]),
                    Textarea::make('mitigation_plan')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    DatePicker::make('mitigation_due_at')->live(),
                ])
                ->columnSpanFull(),
            Section::make('Related quality events')
                ->description('Optional links synced when the draft is saved. Manage additional links from the record view.')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('change_control_id')
                            ->label('Change control')
                            ->options(fn (): array => ChangeControl::query()->latest('id')->limit(50)->pluck('change_number', 'id')->all())
                            ->searchable()
                            ->dehydrated(false),
                        Select::make('deviation_id')
                            ->label('Deviation')
                            ->options(fn (): array => Deviation::query()->latest('id')->limit(50)->pluck('deviation_number', 'id')->all())
                            ->searchable()
                            ->dehydrated(false),
                        Select::make('capa_id')
                            ->label('CAPA')
                            ->options(fn (): array => Capa::query()->latest('id')->limit(50)->pluck('capa_number', 'id')->all())
                            ->searchable()
                            ->dehydrated(false),
                        Select::make('complaint_id')
                            ->label('Complaint')
                            ->options(fn (): array => Complaint::query()->latest('id')->limit(50)->pluck('complaint_number', 'id')->all())
                            ->searchable()
                            ->dehydrated(false),
                        Select::make('audit_finding_id')
                            ->label('Audit finding')
                            ->options(fn (): array => AuditFinding::query()->latest('id')->limit(50)->pluck('finding_number', 'id')->all())
                            ->searchable()
                            ->dehydrated(false),
                        Select::make('csv_validation_project_id')
                            ->label('CSV validation project')
                            ->options(fn (): array => CsvValidationProject::query()->latest('id')->limit(50)->pluck('project_number', 'id')->all())
                            ->searchable()
                            ->dehydrated(false),
                        Select::make('investigation_id')
                            ->label('Investigation')
                            ->options(fn (): array => Investigation::query()->latest('id')->limit(50)->pluck('investigation_number', 'id')->all())
                            ->searchable()
                            ->dehydrated(false),
                    ]),
                ])
                ->columnSpanFull()
                ->collapsed(),
        ]);
    }
}
