<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplateApprovalInstances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SopTemplateApprovalInstanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('templateVersion.template.name')->label('Template'),
                TextEntry::make('templateVersion.version')->label('Version'),
                TextEntry::make('workflow.name')->label('Workflow'),
                TextEntry::make('workflowStep.step_no')->label('Step'),
                TextEntry::make('workflowStep.approvalStepType.name')->label('Step Type'),
                TextEntry::make('workflowStep.role.name')->label('Required Role'),
                TextEntry::make('decision_code')->label('Decision')->badge(),
                TextEntry::make('decider.name')->label('Decided By')->placeholder('—'),
                TextEntry::make('comments')->columnSpanFull()->placeholder('—'),
                TextEntry::make('decided_at')->dateTime()->placeholder('—'),
            ]);
    }
}
