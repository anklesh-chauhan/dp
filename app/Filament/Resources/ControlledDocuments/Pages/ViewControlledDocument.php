<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\Pages;

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\RejectDocumentAction;
use App\Actions\Sop\ReturnDocumentAction;
use App\Actions\Sop\SubmitDocumentAction;
use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Domain\DMS\Actions\IssueDocumentAction;
use App\Domain\DMS\Actions\LockDocumentAction;
use App\Domain\DMS\Actions\UnlockDocumentAction;
use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Domain\DMS\Services\ControlledDocumentSectionReviewService;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Shared\Services\AuditLogService;
use App\Exceptions\WorkflowException;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\PresentsSectionReviewAttention;
use App\Filament\Concerns\ProvidesControlledDocumentPrintPreviewAction;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\Department;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\ReportTemplate;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewControlledDocument extends ViewRecord
{
    use HandlesServiceExceptions;
    use PresentsSectionReviewAttention;
    use ProvidesControlledDocumentPrintPreviewAction;
    use ProvidesRetentionLifecycleActions;

    protected static string $resource = ControlledDocumentResource::class;

    private bool $currentApprovalResolved = false;

    private ?SopApproval $resolvedCurrentApproval = null;

    public function getSubheading(): ?string
    {
        $status = $this->record->documentStatus?->name ?? 'Unknown status';
        $approval = $this->currentApprovalForUser();

        if ($approval instanceof SopApproval) {
            $step = $approval->workflowStep;

            return $this->withSectionReviewAttention("Action required: Step {$step->step_no} · {$step->approvalStepType->name}. Review the document and record your signed decision.");
        }

        if ($this->record->documentStatus?->hasCode(DocumentStatus::UNDER_REVIEW)) {
            $pending = $this->record->currentPendingApprovalStep();

            if ($pending !== null) {
                return $this->withSectionReviewAttention("Under review · Waiting at {$pending->label()}.");
            }

            return $this->withSectionReviewAttention('Under review · Waiting for the next assigned workflow step.');
        }

        return $this->withSectionReviewAttention("Status: {$status}");
    }

    protected function getActions(): array
    {
        return [

            Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Start document approval workflow?')
                ->modalDescription(function (): string {
                    $description = 'The document will be locked for editing and sent to the first eligible reviewer. You can follow each signed decision in Approval History.';
                    $attention = $this->record->sectionReviewAttentionSummary();

                    if ($attention === null) {
                        return $description;
                    }

                    return $description.' '.$attention.' Confirm those sections have been updated before submitting.';
                })
                ->modalSubmitActionLabel('Submit for approval')
                ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                    && Auth::user()?->can('submit', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(SubmitDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Submission Failed',
                        successTitle: 'Document submitted for approval',
                        successBody: 'The document is locked and the first actionable step is now available in the assigned reviewer’s approval queue.',
                        afterSuccess: fn () => $this->refreshFormData(['document_status_id', 'approvals']),
                    );
                }),

            $this->approvalDecisionAction(
                name: 'approveCurrentStep',
                label: 'Approve',
                decision: 'approve',
                color: 'success',
                icon: Heroicon::CheckBadge,
            ),

            $this->controlledDocumentPrintPreviewAction(),

            Action::make('printPdf')
                ->label('View Controlled PDF')
                ->icon(Heroicon::Eye)
                ->schema([
                    Select::make('template')
                        ->label('Print Template')
                        ->options(fn (): array => ReportTemplate::query()
                            ->active()
                            ->where('scope', ReportScope::ControlledDocument)
                            ->where('format', ReportFormat::Pdf)
                            ->pluck('name', 'id')
                            ->all())
                        ->required(),
                ])
                ->action(fn (array $data): mixed => $this->redirect(route('controlled-documents.viewer', [
                    'controlledDocument' => $this->record,
                    'template' => $data['template'],
                ])))
                ->visible(fn (): bool => $this->record->canBePrintedDirectly()
                    && app(ControlledDocumentAccessService::class)->canView(Auth::user(), $this->record)),

            Action::make('issueControlledCopy')
                ->label('Issue Controlled Copy')
                ->icon(Heroicon::DocumentCheck)
                ->schema([
                    Select::make('issuance_type')
                        ->label('Copy type')
                        ->options(fn (): array => $this->record->documentType?->requiresExecutionRecord()
                            ? [
                                DocumentIssuance::TYPE_EXECUTION => 'Writable GMP execution record',
                                DocumentIssuance::TYPE_REFERENCE => 'Read-only reference copy',
                            ]
                            : [DocumentIssuance::TYPE_REFERENCE => 'Read-only reference copy'])
                        ->default(fn (): string => $this->record->documentType?->requiresExecutionRecord()
                            ? DocumentIssuance::TYPE_EXECUTION
                            : DocumentIssuance::TYPE_REFERENCE)
                        ->live()
                        ->required(),
                    Select::make('issued_to_user_id')
                        ->label('Issue to user')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->requiredWithout('issued_to_department_id'),
                    Select::make('issued_to_department_id')
                        ->label('Issue to department')
                        ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->requiredWithout('issued_to_user_id'),
                    TextInput::make('issued_to_location')->maxLength(255),
                    TextInput::make('batch_number')
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isBatchRecord() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    TextInput::make('product_name')
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isBatchRecord() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    Select::make('log_frequency')
                        ->label('Execution frequency')
                        ->options(['hourly' => 'Hourly', 'shift' => 'Every shift', 'daily' => 'Daily'])
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    DatePicker::make('log_period_start')
                        ->label('Log period start')
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    DatePicker::make('log_period_end')
                        ->label('Log period end')
                        ->afterOrEqual('log_period_start')
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->isRepeatingLog() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    Select::make('supervisor_id')
                        ->label('Supervisor reviewer')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => ($this->record->documentType?->requiresSupervisorReview() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION)
                        ->required(fn (Get $get): bool => ($this->record->documentType?->requiresSupervisorReview() ?? false)
                            && $get('issuance_type') === DocumentIssuance::TYPE_EXECUTION),
                    Textarea::make('notes')->rows(2),
                ])
                ->visible(fn (): bool => $this->record->canBeIssued()
                    && (Auth::user()?->can('Issue:DocumentIssuance') ?? false))
                ->action(function (array $data): void {
                    $this->runServiceAction(
                        fn () => app(IssueDocumentAction::class)->execute($this->record, Auth::user(), $data),
                        failureTitle: 'Issuance Failed',
                        successTitle: 'Controlled copy issued.',
                    );
                }),

            EditAction::make()
                ->visible(fn (): bool => ($user = Auth::user()) instanceof User
                    && $user->can('update', $this->record)
                    && $this->record->canBeEditedBy($user)),

            Action::make('createRevision')
                ->label('Create Revision')
                ->icon(Heroicon::DocumentDuplicate)
                ->color('warning')
                ->schema([
                    Textarea::make('revision_reason')
                        ->label('Reason for revision')
                        ->required()
                        ->maxLength(2000),
                ])
                ->visible(fn (): bool => in_array($this->record->documentStatus?->code, [
                    DocumentStatus::APPROVED,
                    DocumentStatus::EFFECTIVE,
                    DocumentStatus::OBSOLETE,
                ], true) && (Auth::user()?->can('revise', $this->record) ?? false))
                ->action(function (array $data): void {
                    $this->runServiceAction(
                        fn () => app(CreateDocumentRevisionAction::class)->execute(
                            $this->record,
                            Auth::user(),
                            $data['revision_reason'],
                        ),
                        failureTitle: 'Revision Failed',
                        successTitle: 'Draft revision created',
                        afterSuccess: fn ($revision) => $this->redirect(
                            ControlledDocumentResource::getUrl('edit', ['record' => $revision])
                        ),
                    );
                }),

            ActionGroup::make([
                ...$this->getDocumentRetentionLifecycleActions(),

                $this->approvalDecisionAction(
                    name: 'returnCurrentStep',
                    label: 'Return for Correction',
                    decision: 'return',
                    color: 'warning',
                    icon: Heroicon::ArrowUturnLeft,
                ),

                $this->approvalDecisionAction(
                    name: 'rejectCurrentStep',
                    label: 'Reject Submission',
                    decision: 'reject',
                    color: 'danger',
                    icon: Heroicon::XCircle,
                ),

                Action::make('lockDocument')
                    ->label('Lock for Editing')
                    ->icon(Heroicon::LockClosed)
                    ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                        && ! $this->record->isLocked()
                        && Auth::user()?->can('lock', $this->record))
                    ->action(function (): void {
                        $this->runServiceAction(
                            fn () => app(LockDocumentAction::class)->execute($this->record, Auth::user()),
                            failureTitle: 'Lock Failed',
                            successTitle: 'Document locked for editing',
                            afterSuccess: fn () => $this->refreshFormData(['locked_by', 'locked_at']),
                        );
                    }),
                Action::make('unlockDocument')
                    ->label('Unlock')
                    ->icon(Heroicon::LockOpen)
                    ->color('warning')
                    ->visible(fn (): bool => $this->record->isLocked()
                        && Auth::user()?->can('unlock', $this->record))
                    ->action(function (): void {
                        $this->runServiceAction(
                            fn () => app(UnlockDocumentAction::class)->execute($this->record, Auth::user()),
                            failureTitle: 'Unlock Failed',
                            successTitle: 'Document unlocked',
                            afterSuccess: fn () => $this->refreshFormData(['locked_by', 'locked_at']),
                        );
                    }),

                Action::make('managePdfAccess')
                    ->label('Manage PDF Access')
                    ->icon(Heroicon::Users)
                    ->color('gray')
                    ->schema([
                        Repeater::make('grants')
                            ->label('Shared With')
                            ->helperText('When this list is empty, normal role permissions apply. Once users are added, only active grants, document owners, and access managers can use the controlled viewer.')
                            ->schema([
                                Select::make('user_id')
                                    ->label('User')
                                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->required(),
                                Toggle::make('can_view')->label('View')->default(true),
                                Toggle::make('can_print')->label('Print'),
                                Toggle::make('can_download')->label('Download'),
                                DateTimePicker::make('expires_at')->label('Expires At')->seconds(false),
                            ])
                            ->columns(5)
                            ->defaultItems(0),
                    ])
                    ->fillForm(fn (): array => [
                        'grants' => $this->record->accessGrants()
                            ->get(['user_id', 'can_view', 'can_print', 'can_download', 'expires_at'])
                            ->map(fn ($grant): array => $grant->toArray())
                            ->all(),
                    ])
                    ->action(function (array $data): void {
                        DB::transaction(function () use ($data): void {
                            $grants = collect($data['grants'] ?? []);
                            $userIds = $grants->pluck('user_id')->filter()->map(fn ($id): int => (int) $id);

                            $this->record->accessGrants()->whereNotIn('user_id', $userIds)->delete();

                            foreach ($grants as $grant) {
                                $this->record->accessGrants()->updateOrCreate(
                                    ['user_id' => (int) $grant['user_id']],
                                    [
                                        'can_view' => (bool) ($grant['can_view'] ?? false),
                                        'can_print' => (bool) ($grant['can_print'] ?? false),
                                        'can_download' => (bool) ($grant['can_download'] ?? false),
                                        'expires_at' => $grant['expires_at'] ?? null,
                                        'granted_by' => Auth::id(),
                                    ],
                                );
                            }
                        });

                        app(AuditLogService::class)->log(
                            action: SopAuditLog::ACTION_PDF_ACCESS_UPDATED,
                            newValues: [
                                'grants' => collect($data['grants'] ?? [])->map(fn (array $grant): array => [
                                    'user_id' => (int) $grant['user_id'],
                                    'can_view' => (bool) ($grant['can_view'] ?? false),
                                    'can_print' => (bool) ($grant['can_print'] ?? false),
                                    'can_download' => (bool) ($grant['can_download'] ?? false),
                                    'expires_at' => $grant['expires_at'] ?? null,
                                ])->all(),
                            ],
                            document: $this->record,
                        );
                    })
                    ->visible(fn (): bool => app(ControlledDocumentAccessService::class)->canManage(Auth::user(), $this->record)),
            ]),
        ];
    }

    private function approvalDecisionAction(
        string $name,
        string $label,
        string $decision,
        string $color,
        Heroicon $icon,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->modalHeading(fn (): string => $this->approvalDecisionHeading($label))
            ->modalDescription(match ($decision) {
                'approve' => 'Your decision will be electronically signed. If this is the final mandatory step, the controlled document will become effective.',
                'return' => 'The document will return to Draft and unlock for correction. The maker can revise and submit it again.',
                default => 'The current submission will be rejected and its remaining workflow steps will close.',
            })
            ->modalSubmitActionLabel($label)
            ->schema([
                ApprovalNarrativeTextarea::decisionRationale(
                    helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed audit trail.',
                    context: fn (): array => [
                        'record_type' => 'Controlled document approval',
                        'subject' => $this->record->document_number
                            ?? $this->record->title
                            ?? (string) $this->record->getKey(),
                        'department' => $this->record->department?->name,
                        'decision' => $label,
                    ],
                ),
            ])
            ->extraModalFooterActions(function (): array {
                $preview = $this->controlledDocumentPrintPreviewModalAction($this->record);

                return $preview instanceof Action ? [$preview] : [];
            })
            ->visible(fn (): bool => $this->currentApprovalForUser() instanceof SopApproval)
            ->action(function (array $data) use ($decision, $label): void {
                $this->runServiceAction(
                    callback: function () use ($decision, $data): SopApproval {
                        $approval = $this->currentApprovalForUser();

                        if (! $approval instanceof SopApproval) {
                            throw new WorkflowException(message: 'This approval step is no longer available. Refresh the page to see the current workflow status.');
                        }

                        return match ($decision) {
                            'approve' => app(ApproveDocumentAction::class)->execute($approval, Auth::user(), $data['comments']),
                            'return' => app(ReturnDocumentAction::class)->execute($approval, Auth::user(), $data['comments']),
                            default => app(RejectDocumentAction::class)->execute($approval, Auth::user(), $data['comments']),
                        };
                    },
                    failureTitle: "{$label} Failed",
                    successTitle: "Decision recorded: {$label}",
                    successBody: 'Your electronic signature and rationale were saved. The workflow status has been updated.',
                    afterSuccess: function (): void {
                        $this->currentApprovalResolved = false;
                        $this->resolvedCurrentApproval = null;
                        $this->record->refresh();
                        $this->refreshFormData(['document_status_id']);
                    },
                );
            });
    }

    private function approvalDecisionHeading(string $label): string
    {
        $approval = $this->currentApprovalForUser();

        if (! $approval instanceof SopApproval) {
            return $label;
        }

        return "{$label}: Step {$approval->workflowStep->step_no} · {$approval->workflowStep->approvalStepType->name}";
    }

    private function currentApprovalForUser(): ?SopApproval
    {
        if ($this->currentApprovalResolved) {
            return $this->resolvedCurrentApproval;
        }

        $this->currentApprovalResolved = true;
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return $this->resolvedCurrentApproval = app(ControlledDocumentSectionReviewService::class)
            ->actionableApprovalFor($this->record, $user);
    }
}
