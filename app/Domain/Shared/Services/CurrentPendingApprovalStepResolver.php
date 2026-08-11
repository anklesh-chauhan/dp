<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Domain\Shared\Support\PendingApprovalStep;
use App\Models\ApprovalDecision;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\SopApproval;
use Illuminate\Support\Collection;

final class CurrentPendingApprovalStepResolver
{
    public function forSubject(ApprovableSubject $subject): ?PendingApprovalStep
    {
        return match (true) {
            $subject instanceof ControlledDocument => $this->forControlledDocument($subject),
            $subject instanceof DocumentTemplateVersion => $this->forTemplateVersion($subject),
            $subject instanceof DocumentTemplate => $this->forDocumentTemplate($subject),
            default => null,
        };
    }

    public function forControlledDocument(ControlledDocument $document): ?PendingApprovalStep
    {
        if (! $document->documentStatus?->hasCode(DocumentStatus::UNDER_REVIEW)) {
            return null;
        }

        $document->loadMissing([
            'approvals.workflowStep.approvalStepType',
            'approvals.workflowStep.role',
            'approvals.workflowStep.department',
            'approvals.approvalDecision',
        ]);

        /** @var Collection<int, SopApproval> $approvals */
        $approvals = $document->approvals
            ->sortBy(fn (SopApproval $approval): int => (int) $approval->workflowStep->step_no)
            ->values();

        $current = $approvals->first(
            fn (SopApproval $approval): bool => $this->isDocumentStepActionable($approval, $approvals),
        );

        if (! $current instanceof SopApproval) {
            return null;
        }

        $step = $current->workflowStep;

        return new PendingApprovalStep(
            stepNo: (int) $step->step_no,
            roleName: (string) $step->role->name,
            stepTypeName: $step->approvalStepType?->name,
            departmentName: $step->department?->name,
        );
    }

    public function forDocumentTemplate(DocumentTemplate $template): ?PendingApprovalStep
    {
        $draft = $template->relationLoaded('latestDraftVersion')
            ? $template->latestDraftVersion
            : $template->latestDraftVersion()->first();

        if (! $draft instanceof DocumentTemplateVersion) {
            return null;
        }

        return $this->forTemplateVersion($draft);
    }

    public function forTemplateVersion(DocumentTemplateVersion $version): ?PendingApprovalStep
    {
        if (! in_array($version->approval_status, [
            TemplateApprovalStatus::Submitted,
            TemplateApprovalStatus::Reviewed,
        ], true)) {
            return null;
        }

        $version->loadMissing([
            'approvalInstances.workflowStep.approvalStepType',
            'approvalInstances.workflowStep.role',
            'approvalInstances.workflowStep.department',
        ]);

        $latestSubmissionUuid = $version->approvalInstances
            ->sortByDesc('id')
            ->value('submission_uuid');

        if ($latestSubmissionUuid === null) {
            return null;
        }

        /** @var Collection<int, DocumentTemplateApprovalInstance> $instances */
        $instances = $version->approvalInstances
            ->where('submission_uuid', $latestSubmissionUuid)
            ->sortBy(fn (DocumentTemplateApprovalInstance $instance): int => (int) $instance->workflowStep->step_no)
            ->values();

        $current = $instances->first(
            fn (DocumentTemplateApprovalInstance $instance): bool => $this->isTemplateStepActionable($instance, $instances),
        );

        if (! $current instanceof DocumentTemplateApprovalInstance) {
            return null;
        }

        $step = $current->workflowStep;

        return new PendingApprovalStep(
            stepNo: (int) $step->step_no,
            roleName: (string) $step->role->name,
            stepTypeName: $step->approvalStepType?->name,
            departmentName: $step->department?->name,
        );
    }

    /**
     * @param  Collection<int, SopApproval>  $approvals
     */
    private function isDocumentStepActionable(SopApproval $approval, Collection $approvals): bool
    {
        if (! $approval->approvalDecision?->hasCode(ApprovalDecision::PENDING)) {
            return false;
        }

        return ! $approvals
            ->filter(fn (SopApproval $item): bool => $item->workflowStep->step_no < $approval->workflowStep->step_no
                && $item->workflowStep->is_mandatory)
            ->contains(fn (SopApproval $item): bool => ! $item->approvalDecision?->hasCode(ApprovalDecision::APPROVED));
    }

    /**
     * @param  Collection<int, DocumentTemplateApprovalInstance>  $instances
     */
    private function isTemplateStepActionable(
        DocumentTemplateApprovalInstance $instance,
        Collection $instances,
    ): bool {
        if ($instance->decision_code !== ApprovalDecisionCode::PENDING->value) {
            return false;
        }

        return ! $instances
            ->filter(fn (DocumentTemplateApprovalInstance $item): bool => $item->workflowStep->step_no < $instance->workflowStep->step_no
                && $item->workflowStep->is_mandatory)
            ->contains(fn (DocumentTemplateApprovalInstance $item): bool => $item->decision_code !== ApprovalDecisionCode::APPROVED->value);
    }
}
