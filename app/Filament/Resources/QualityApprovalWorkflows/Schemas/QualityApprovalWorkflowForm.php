<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows\Schemas;

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\QualityWorkflowSubjects;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

final class QualityApprovalWorkflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('workflow_code')
                    ->label('Workflow code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : strtoupper($state))
                    ->disabled(fn (?QualityApprovalWorkflow $record): bool => $record?->hasApprovalHistory() ?? false)
                    ->helperText('Unique identifier, for example QWF-DEV-QA.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?QualityApprovalWorkflow $record): bool => $record?->hasApprovalHistory() ?? false),
                Select::make('subject_type')
                    ->label('Record type')
                    ->options(QualityWorkflowSubjects::options())
                    ->required()
                    ->default(Deviation::class)
                    ->disabled(fn (?QualityApprovalWorkflow $record): bool => $record?->hasApprovalHistory() ?? false)
                    ->helperText('Department workflows take precedence over a global workflow for the same record type.'),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(fn (?QualityApprovalWorkflow $record): bool => $record?->hasApprovalHistory() ?? false)
                    ->placeholder('Global (all departments)'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Textarea::make('description')
                    ->rows(3)
                    ->disabled(fn (?QualityApprovalWorkflow $record): bool => $record?->hasApprovalHistory() ?? false)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
