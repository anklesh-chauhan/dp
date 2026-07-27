<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\RelationManagers;

use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Models\SopTemplateApprovalInstance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
                    ->state(fn (SopTemplateApprovalInstance $record): string => $record->signature_hash === null
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
            ->schema([
                Textarea::make('comments')
                    ->label('Decision reason')
                    ->required()
                    ->maxLength(2_000),
            ])
            ->visible(function (SopTemplateApprovalInstance $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && app(TemplateApprovalDecisionService::class)->canDecide($record, $user);
            })
            ->action(function (SopTemplateApprovalInstance $record, array $data) use ($decision, $label): void {
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
