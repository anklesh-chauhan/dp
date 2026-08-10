<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\RejectDocumentAction;
use App\Actions\Sop\ReturnDocumentAction;
use App\Domain\DMS\Services\SopApprovalDecisionAuthorizationAdapter;
use App\Exceptions\WorkflowException;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Models\ApprovalDecision;
use App\Models\SopApproval;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalRelationManager extends RelationManager
{
    use HandlesServiceExceptions;

    protected static string $relationship = 'approvals';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workflowStep.step_no')->label('Step')->sortable(),
                TextColumn::make('workflowStep.approvalStepType.name')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('workflowStep.role.name')->label('Role'),
                TextColumn::make('workflowStep.department.name')
                    ->label('Department')
                    ->placeholder('Same as document'),
                TextColumn::make('approvalDecision.name')
                    ->label('Decision')
                    ->badge(),
                TextColumn::make('approver.name')->label('Decided By'),
                TextColumn::make('approved_at')->dateTime(),
                TextColumn::make('comments')->limit(50)->toggleable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve Step')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->modalHeading('Approve this workflow step?')
                    ->modalDescription('Your rationale and electronic signature will be added to the permanent approval history.')
                    ->schema([
                        Textarea::make('comments')
                            ->label('Decision rationale')
                            ->required()
                            ->maxLength(2_000),
                    ])
                    ->visible(fn (SopApproval $record): bool => $this->canDecide($record)
                        && $record->approvalDecision?->hasCode(ApprovalDecision::PENDING))
                    ->action(function ($record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(ApproveDocumentAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['comments'] ?? null,
                            ),
                            failureTitle: 'Approval Failed',
                            successTitle: 'Document approved successfully.',
                        );
                    }),
                Action::make('return')
                    ->label('Return for Correction')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->modalHeading('Return this document for correction?')
                    ->modalDescription('The document will return to Draft and unlock so the maker can correct and resubmit it.')
                    ->schema([Textarea::make('comments')->label('Correction required')->required()->maxLength(2_000)])
                    ->visible(fn (SopApproval $record): bool => $this->canDecide($record)
                        && $record->approvalDecision?->hasCode(ApprovalDecision::PENDING))
                    ->action(function ($record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(ReturnDocumentAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['comments'] ?? null,
                            ),
                            failureTitle: 'Return Failed',
                            successTitle: 'Document returned to maker.',
                        );
                    }),
                Action::make('reject')
                    ->label('Reject Submission')
                    ->color('danger')
                    ->icon(Heroicon::XCircle)
                    ->modalHeading('Reject this approval submission?')
                    ->modalDescription('The current approval cycle will close. Record a clear reason for the signed audit trail.')
                    ->schema([Textarea::make('comments')->label('Rejection reason')->required()->maxLength(2_000)])
                    ->visible(fn (SopApproval $record): bool => $this->canDecide($record)
                        && $record->approvalDecision?->hasCode(ApprovalDecision::PENDING))
                    ->action(function ($record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(RejectDocumentAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['comments'] ?? null,
                            ),
                            failureTitle: 'Reject Failed',
                            successTitle: 'Document rejected.',
                        );
                    }),
            ]);
    }

    private function canDecide(SopApproval $approval): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        try {
            app(SopApprovalDecisionAuthorizationAdapter::class)->authorizeDecision($approval, $user);

            return true;
        } catch (WorkflowException) {
            return false;
        }
    }
}
