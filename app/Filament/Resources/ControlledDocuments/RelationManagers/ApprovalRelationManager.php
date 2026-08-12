<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\RejectDocumentAction;
use App\Actions\Sop\ReturnDocumentAction;
use App\Domain\DMS\Services\SopApprovalDecisionAuthorizationAdapter;
use App\Exceptions\WorkflowException;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\ApprovalDecision;
use App\Models\ControlledDocument;
use App\Models\SopApproval;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalRelationManager extends RelationManager
{
    use HandlesServiceExceptions;
    use ProvidesControlledDocumentPrintPreviewAction;

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
            ->headerActions($this->printPreviewHeaderActions())
            ->recordActions([
                $this->decisionRecordAction(
                    name: 'approve',
                    label: 'Approve Step',
                    decision: 'approve',
                    color: 'success',
                    icon: Heroicon::CheckBadge,
                    heading: 'Approve this workflow step?',
                    description: 'Your rationale and electronic signature will be added to the permanent approval history.',
                    commentsLabel: 'Decision rationale',
                    successTitle: 'Document approved successfully.',
                ),
                $this->decisionRecordAction(
                    name: 'return',
                    label: 'Return for Correction',
                    decision: 'return',
                    color: 'warning',
                    icon: Heroicon::ArrowUturnLeft,
                    heading: 'Return this document for correction?',
                    description: 'The document will return to Draft and unlock so the maker can correct and resubmit it.',
                    commentsLabel: 'Correction required',
                    successTitle: 'Document returned to maker.',
                ),
                $this->decisionRecordAction(
                    name: 'reject',
                    label: 'Reject Submission',
                    decision: 'reject',
                    color: 'danger',
                    icon: Heroicon::XCircle,
                    heading: 'Reject this approval submission?',
                    description: 'The current approval cycle will close. Record a clear reason for the signed audit trail.',
                    commentsLabel: 'Rejection reason',
                    successTitle: 'Document rejected.',
                ),
            ]);
    }

    /**
     * @return list<Action>
     */
    private function printPreviewHeaderActions(): array
    {
        $document = $this->getOwnerRecord();

        if (! $document instanceof ControlledDocument) {
            return [];
        }

        $preview = $this->controlledDocumentPrintPreviewModalAction($document, 'previewWithPrintTemplate');

        return $preview instanceof Action ? [$preview] : [];
    }

    private function decisionRecordAction(
        string $name,
        string $label,
        string $decision,
        string $color,
        Heroicon $icon,
        string $heading,
        string $description,
        string $commentsLabel,
        string $successTitle,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->modalHeading($heading)
            ->modalDescription($description)
            ->schema(fn (SopApproval $record): array => [
                ApprovalNarrativeTextarea::decisionRationale(
                    label: $commentsLabel,
                    context: [
                        'record_type' => 'Controlled document approval',
                        'subject' => ($document = $this->getOwnerRecord()) instanceof ControlledDocument
                            ? ($document->document_number ?? $document->title ?? (string) $document->getKey())
                            : null,
                        'department' => $document instanceof ControlledDocument
                            ? $document->department?->name
                            : null,
                        'decision' => $label,
                        'extra' => 'Workflow step '.$record->workflowStep?->step_no,
                    ],
                ),
            ])
            ->extraModalFooterActions(function (): array {
                $document = $this->getOwnerRecord();

                if (! $document instanceof ControlledDocument) {
                    return [];
                }

                $preview = $this->controlledDocumentPrintPreviewModalAction($document);

                return $preview instanceof Action ? [$preview] : [];
            })
            ->visible(fn (SopApproval $record): bool => $this->canDecide($record)
                && $record->approvalDecision?->hasCode(ApprovalDecision::PENDING))
            ->action(function (SopApproval $record, array $data) use ($decision, $label, $successTitle): void {
                $this->runServiceAction(
                    fn () => match ($decision) {
                        'approve' => app(ApproveDocumentAction::class)->execute(
                            $record,
                            Auth::user(),
                            $data['comments'] ?? null,
                        ),
                        'return' => app(ReturnDocumentAction::class)->execute(
                            $record,
                            Auth::user(),
                            $data['comments'] ?? null,
                        ),
                        default => app(RejectDocumentAction::class)->execute(
                            $record,
                            Auth::user(),
                            $data['comments'] ?? null,
                        ),
                    },
                    failureTitle: "{$label} Failed",
                    successTitle: $successTitle,
                );
            });
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
