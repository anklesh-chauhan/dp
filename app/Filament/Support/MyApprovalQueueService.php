<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\DMS\Services\SopApprovalDecisionAuthorizationAdapter;
use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Services\QualityApprovalDecisionAuthorization;
use App\Enums\ProductModule;
use App\Exceptions\WorkflowException;
use App\Filament\Resources\Deviations\DeviationResource;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use App\Filament\Resources\SopTemplateApprovalInstances\SopTemplateApprovalInstanceResource;
use App\Models\SopApproval;
use App\Models\SopTemplateApprovalInstance;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Collection;

class MyApprovalQueueService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly SopApprovalDecisionAuthorizationAdapter $documentAuthorization,
        private readonly TemplateApprovalDecisionService $templateDecisions,
        private readonly QualityApprovalDecisionAuthorization $qualityAuthorization,
    ) {}

    /**
     * @return Collection<string, array{
     *     id: string,
     *     module: string,
     *     work_type: string,
     *     reference: string,
     *     title: string,
     *     department: string,
     *     step: int,
     *     step_type: string,
     *     required_role: string,
     *     submitted_at: string,
     *     review_url: string
     * }>
     */
    public function forUser(User $user): Collection
    {
        return collect()
            ->merge($this->documentApprovals($user))
            ->merge($this->templateApprovals($user))
            ->merge($this->qualityApprovals($user))
            ->sortByDesc('submitted_at')
            ->keyBy('id');
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function documentApprovals(User $user): Collection
    {
        return SopApproval::query()
            ->pending()
            ->visibleToUser($user)
            ->with([
                'document.department',
                'workflowStep.approvalStepType',
                'workflowStep.role',
                'approvalDecision',
            ])
            ->get()
            ->filter(fn (SopApproval $approval): bool => $this->canDecideDocument($approval, $user))
            ->map(fn (SopApproval $approval): array => [
                'id' => "sop-document:{$approval->getKey()}",
                'module' => 'DMS',
                'work_type' => 'SOP Document',
                'reference' => (string) $approval->document->document_number,
                'title' => (string) $approval->document->title,
                'department' => (string) ($approval->document->department?->name ?? 'Global'),
                'step' => (int) $approval->workflowStep->step_no,
                'step_type' => (string) $approval->workflowStep->approvalStepType->name,
                'required_role' => (string) $approval->workflowStep->role->name,
                'submitted_at' => $approval->created_at->toISOString(),
                'review_url' => SopDocumentResource::getUrl('view', ['record' => $approval->document_id]),
            ]);
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function templateApprovals(User $user): Collection
    {
        return SopTemplateApprovalInstance::query()
            ->where('decision_code', 'pending')
            ->with([
                'templateVersion.template.department',
                'workflowStep.approvalStepType',
                'workflowStep.role',
            ])
            ->get()
            ->filter(fn (SopTemplateApprovalInstance $instance): bool => $this->templateDecisions->canDecide($instance, $user))
            ->map(fn (SopTemplateApprovalInstance $instance): array => [
                'id' => "sop-template:{$instance->getKey()}",
                'module' => 'DMS',
                'work_type' => 'SOP Template',
                'reference' => "{$instance->templateVersion->template->code} v{$instance->templateVersion->version}",
                'title' => (string) $instance->templateVersion->template->name,
                'department' => (string) ($instance->templateVersion->template->department?->name ?? 'Global'),
                'step' => (int) $instance->workflowStep->step_no,
                'step_type' => (string) $instance->workflowStep->approvalStepType->name,
                'required_role' => (string) $instance->workflowStep->role->name,
                'submitted_at' => ($instance->templateVersion->submitted_at ?? $instance->created_at)->toISOString(),
                'review_url' => SopTemplateApprovalInstanceResource::getUrl('view', ['record' => $instance]),
            ]);
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function qualityApprovals(User $user): Collection
    {
        if (! $this->moduleManager->enabled(ProductModule::QMS)) {
            return collect();
        }

        return QualityApprovalInstance::query()
            ->where('decision_code', 'pending')
            ->with([
                'subject',
                'workflowStep.role',
            ])
            ->get()
            ->filter(fn (QualityApprovalInstance $instance): bool => $this->qualityAuthorization->canDecide($instance, $user))
            ->filter(fn (QualityApprovalInstance $instance): bool => $instance->subject instanceof Deviation)
            ->map(function (QualityApprovalInstance $instance): array {
                /** @var Deviation $deviation */
                $deviation = $instance->subject;
                $deviation->loadMissing('department');

                return [
                    'id' => "qms-deviation:{$instance->getKey()}",
                    'module' => 'QMS',
                    'work_type' => 'Deviation',
                    'reference' => (string) $deviation->deviation_number,
                    'title' => (string) $deviation->title,
                    'department' => (string) ($deviation->department?->name ?? 'Global'),
                    'step' => (int) $instance->workflowStep->step_no,
                    'step_type' => 'Approval',
                    'required_role' => (string) $instance->workflowStep->role->name,
                    'submitted_at' => $instance->created_at->toISOString(),
                    'review_url' => DeviationResource::getUrl('view', ['record' => $deviation]),
                ];
            });
    }

    private function canDecideDocument(SopApproval $approval, User $user): bool
    {
        try {
            $this->documentAuthorization->authorizeDecision($approval, $user);

            return true;
        } catch (WorkflowException) {
            return false;
        }
    }
}
