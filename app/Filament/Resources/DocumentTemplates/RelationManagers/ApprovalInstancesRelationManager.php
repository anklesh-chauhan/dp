<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\RelationManagers;

use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApprovalInstancesRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalInstances';

    protected static ?string $title = 'Template Workflow Approvals';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('templateVersion.version')->label('Version'),
                TextColumn::make('workflow.name')->label('Workflow'),
                TextColumn::make('workflowStep.step_no')->label('Step')->sortable(),
                TextColumn::make('workflowStep.approvalStepType.name')->label('Type'),
                TextColumn::make('workflowStep.role.name')->label('Required Role'),
                TextColumn::make('decision_code')->label('Decision')->badge(),
                TextColumn::make('decider.name')->label('Decided By')->placeholder('—'),
                TextColumn::make('comments')->wrap()->placeholder('—'),
                TextColumn::make('signature_status')
                    ->label('Signature')
                    ->state(fn (DocumentTemplateApprovalInstance $record): string => $record->signature_hash === null
                        ? 'Pending'
                        : (app(ElectronicSignatureVerifier::class)->isValid($record) ? 'Valid' : 'Invalid'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Valid' => 'success',
                        'Invalid' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('decided_at')->dateTime()->placeholder('—'),
            ])
            ->defaultSort('id')
            ->recordActions([
                $this->decisionAction('approve', 'Approve', ApprovalDecisionCode::APPROVED, 'success'),
                $this->decisionAction('reject', 'Reject', ApprovalDecisionCode::REJECTED, 'danger'),
                $this->decisionAction('return', 'Return for Correction', ApprovalDecisionCode::RETURNED, 'warning'),
            ]);
    }

    private function decisionAction(
        string $name,
        string $label,
        ApprovalDecisionCode $decision,
        string $color,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema(fn (DocumentTemplateApprovalInstance $record): array => [
                ApprovalNarrativeTextarea::decisionRationale(
                    label: 'Decision reason',
                    context: [
                        'record_type' => 'Document template approval',
                        'subject' => trim(implode(' · ', array_filter([
                            $record->templateVersion?->template?->code,
                            $record->templateVersion?->template?->name,
                            $record->templateVersion !== null
                                ? 'v'.$record->templateVersion->version
                                : null,
                        ]))),
                        'department' => $record->templateVersion?->template?->department?->name,
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(function (DocumentTemplateApprovalInstance $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && app(TemplateApprovalDecisionService::class)->canDecide($record, $user);
            })
            ->action(function (DocumentTemplateApprovalInstance $record, array $data) use ($decision, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(TemplateApprovalDecisionService::class)->decide(
                    $record,
                    $user,
                    $decision,
                    $data['comments'],
                    request()->ip(),
                    request()->userAgent(),
                );

                Notification::make()
                    ->success()
                    ->title("Template approval: {$label}")
                    ->send();
            });
    }
}
