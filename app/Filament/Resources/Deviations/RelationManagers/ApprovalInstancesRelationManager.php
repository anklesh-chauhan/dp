<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\RelationManagers;

use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Services\DeviationApprovalDecisionService;
use App\Domain\QMS\Services\QualityApprovalDecisionAuthorization;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ApprovalInstancesRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalInstances';

    protected static ?string $title = 'Approval Workflow';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workflow.name')->label('Workflow'),
                TextColumn::make('workflowStep.step_no')->label('Step')->sortable(),
                TextColumn::make('workflowStep.role.name')->label('Required Role'),
                TextColumn::make('workflowStep.department.name')->label('Department')->placeholder('Record Department'),
                TextColumn::make('decision_code')->label('Decision')->badge(),
                TextColumn::make('decider.name')->label('Decided By')->placeholder('—'),
                TextColumn::make('decided_at')->dateTime()->placeholder('—'),
            ])
            ->recordActions([
                $this->decisionAction('approve', 'Approve', 'approve', 'success'),
                $this->decisionAction('reject', 'Reject', 'reject', 'danger'),
                $this->decisionAction('return', 'Return', 'return', 'warning'),
            ])
            ->defaultSort('workflow_step_id');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    private function decisionAction(
        string $name,
        string $label,
        string $method,
        string $color,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema(fn (QualityApprovalInstance $record): array => [
                ApprovalNarrativeTextarea::decisionRationale(
                    label: 'Decision Reason',
                    context: [
                        'record_type' => 'Deviation approval',
                        'subject' => $this->getOwnerRecord()->deviation_number
                            ?? (string) $this->getOwnerRecord()->getKey(),
                        'department' => $this->getOwnerRecord()->department?->name,
                        'decision' => $label,
                        'extra' => 'Workflow step '.$record->workflowStep?->step_no,
                    ],
                ),
            ])
            ->authorize(fn (QualityApprovalInstance $record): bool => $this->canDecide($record))
            ->visible(fn (QualityApprovalInstance $record): bool => $this->canDecide($record))
            ->action(function (
                QualityApprovalInstance $record,
                array $data,
            ) use ($method, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(DeviationApprovalDecisionService::class)->{$method}(
                    $record,
                    $user,
                    $data['comments'],
                );
                $this->getOwnerRecord()->refresh();
                $this->resetTable();

                Notification::make()
                    ->success()
                    ->title("Quality approval: {$label}")
                    ->send();
            });
    }

    private function canDecide(QualityApprovalInstance $record): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && app(QualityApprovalDecisionAuthorization::class)->canDecide($record, $user);
    }
}
