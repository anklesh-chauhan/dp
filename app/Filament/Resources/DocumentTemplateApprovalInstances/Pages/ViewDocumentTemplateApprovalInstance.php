<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplateApprovalInstances\Pages;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Pages\MyApprovalQueue;
use App\Filament\Resources\DocumentTemplateApprovalInstances\DocumentTemplateApprovalInstanceResource;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewDocumentTemplateApprovalInstance extends ViewRecord
{
    use HandlesServiceExceptions;

    protected static string $resource = DocumentTemplateApprovalInstanceResource::class;

    public function getSubheading(): ?string
    {
        $this->record->loadMissing(['templateVersion.template', 'workflowStep.approvalStepType']);

        if ($this->record->decision_code !== ApprovalDecisionCode::PENDING->value) {
            return 'This workflow step has been decided. The signed result is shown below.';
        }

        return "Action required: Review {$this->record->templateVersion->template->code} version {$this->record->templateVersion->version} and record your signed decision for Step {$this->record->workflowStep->step_no}.";
    }

    /** @return array<int, Action> */
    protected function getActions(): array
    {
        return [

            $this->decisionAction(
                name: 'approve',
                label: 'Approve',
                decision: ApprovalDecisionCode::APPROVED,
                color: 'success',
                icon: Heroicon::CheckBadge,
            ),

            Action::make('previewTemplate')
                ->label('Print Preview')
                ->icon(Heroicon::Eye)
                ->color('gray')
                ->tooltip('Opens the print layout for this template version. This is not controlled printing.')
                ->url(fn (): string => route('document-templates.versions.preview', [
                    'documentTemplate' => $this->record->templateVersion->template,
                    'documentTemplateVersion' => $this->record->templateVersion,
                ]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->templateVersion?->template?->report_template_id !== null),

            ActionGroup::make([
                Action::make('viewTemplate')
                    ->label('View Template Record')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('gray')
                    ->url(fn (): string => DocumentTemplateResource::getUrl('view', [
                        'record' => $this->record->templateVersion->template,
                    ])),

                $this->decisionAction(
                    name: 'return',
                    label: 'Return for Correction',
                    decision: ApprovalDecisionCode::RETURNED,
                    color: 'warning',
                    icon: Heroicon::ArrowUturnLeft,
                ),
                $this->decisionAction(
                    name: 'reject',
                    label: 'Reject Submission',
                    decision: ApprovalDecisionCode::REJECTED,
                    color: 'danger',
                    icon: Heroicon::XCircle,
                ),
                Action::make('backToQueue')
                    ->label('Back to My Queue')
                    ->icon(Heroicon::QueueList)
                    ->color('gray')
                    ->url(MyApprovalQueue::getUrl()),
            ]),
        ];
    }

    private function decisionAction(
        string $name,
        string $label,
        ApprovalDecisionCode $decision,
        string $color,
        Heroicon $icon,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->modalHeading("{$label}: Template workflow step")
            ->modalDescription(match ($decision) {
                ApprovalDecisionCode::APPROVED => 'Your decision will be electronically signed. If this is the final mandatory step, the version becomes ready to publish.',
                ApprovalDecisionCode::RETURNED => 'The version will return to Draft so the author can correct it and submit a new review cycle.',
                default => 'The current submission will be rejected and its remaining workflow steps will close.',
            })
            ->modalSubmitActionLabel($label)
            ->schema([
                ApprovalNarrativeTextarea::decisionRationale(
                    context: fn (): array => [
                        'record_type' => 'Document template approval',
                        'subject' => trim(implode(' · ', array_filter([
                            $this->record->templateVersion?->template?->code,
                            $this->record->templateVersion?->template?->name,
                            $this->record->templateVersion !== null
                                ? 'v'.$this->record->templateVersion->version
                                : null,
                        ]))),
                        'department' => $this->record->templateVersion?->template?->department?->name,
                        'decision' => $label,
                        'extra' => ($submissionNote = $this->record->templateVersion?->approvalEvents
                            ?->where('to_status', TemplateApprovalStatus::Submitted)
                            ->last()?->reason) !== null && filled($submissionNote)
                            ? 'Submission note: '.$submissionNote
                            : null,
                    ],
                ),
            ])
            ->extraModalFooterActions(function (): array {
                $template = $this->record->templateVersion?->template;
                $version = $this->record->templateVersion;

                if ($template === null || $version === null || $template->report_template_id === null) {
                    return [];
                }

                return [
                    Action::make('openPrintPreview')
                        ->label('Print Preview')
                        ->icon(Heroicon::Eye)
                        ->color('gray')
                        ->url(route('document-templates.versions.preview', [
                            'documentTemplate' => $template,
                            'documentTemplateVersion' => $version,
                        ]))
                        ->openUrlInNewTab(),
                ];
            })
            ->visible(fn (): bool => $this->canDecide())
            ->action(function (array $data) use ($decision, $label): void {
                /** @var User $user */
                $user = Auth::user();

                $this->runServiceAction(
                    callback: fn (): DocumentTemplateApprovalInstance => app(TemplateApprovalDecisionService::class)->decide(
                        $this->record,
                        $user,
                        $decision,
                        $data['comments'],
                        request()->ip(),
                        request()->userAgent(),
                    ),
                    failureTitle: "{$label} Failed",
                    successTitle: "Decision recorded: {$label}",
                    successBody: 'Your electronic signature and rationale were saved. The workflow status has been updated.',
                    afterSuccess: function (): void {
                        $this->record->refresh();
                    },
                );
            });
    }

    private function canDecide(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && app(TemplateApprovalDecisionService::class)->canDecide($this->record, $user);
    }
}
