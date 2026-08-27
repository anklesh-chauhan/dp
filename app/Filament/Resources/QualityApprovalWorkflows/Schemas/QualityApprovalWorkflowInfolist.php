<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows\Schemas;

use App\Domain\QMS\QualityWorkflowSubjects;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class QualityApprovalWorkflowInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Quality workflow')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('workflow_code')->label('Workflow code')->copyable(),
                        TextEntry::make('name'),
                        TextEntry::make('subject_type')
                            ->label('Record type')
                            ->formatStateUsing(fn (?string $state): string => QualityWorkflowSubjects::label($state)),
                        TextEntry::make('department.name')
                            ->label('Department')
                            ->placeholder('Global (all departments)'),
                        IconEntry::make('is_active')->label('Active')->boolean(),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
