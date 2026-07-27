<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Enums\ProductModule;
use App\Models\SopTemplate;
use App\Models\SopTemplateApprovalEvent;
use App\Models\SopTemplateApprovalInstance;
use App\Models\SopTemplateVersion;
use App\Models\SopWorkflow;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TemplateApprovalService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly SopWorkflowDefinitionSelector $workflowSelector,
    ) {}

    public function submit(
        SopTemplate $template,
        User $actor,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): SopTemplateVersion {
        $this->moduleManager->ensureEnabled(ProductModule::DMS);

        if (! $actor->can('Submit:SopTemplate')) {
            throw new AuthorizationException('You do not have permission to submit SOP templates.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'An attributable reason is required.']);
        }

        return DB::transaction(function () use ($template, $actor, $reason): SopTemplateVersion {
            $template = SopTemplate::query()->lockForUpdate()->findOrFail($template->id);

            if ($template->isInRetentionLifecycle()) {
                throw ValidationException::withMessages([
                    'template' => 'Templates in the retention lifecycle cannot enter approval.',
                ]);
            }

            $version = $template->versions()
                ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::DRAFT))
                ->latest('version')
                ->lockForUpdate()
                ->first();

            if ($version === null) {
                throw ValidationException::withMessages([
                    'version' => 'Create a draft template version before starting approval.',
                ]);
            }

            if (! $version->approval_status->isEditable()) {
                throw ValidationException::withMessages([
                    'approval_status' => "A {$version->approval_status->label()} template version cannot be submitted.",
                ]);
            }

            $workflow = $this->workflowSelector->selectFor($version);

            if (! $workflow instanceof SopWorkflow) {
                throw ValidationException::withMessages([
                    'workflow' => 'Configure an active department or global SOP workflow before submitting the template.',
                ]);
            }

            $steps = $workflow->steps()->orderBy('step_no')->get();

            if ($steps->isEmpty()) {
                throw ValidationException::withMessages([
                    'workflow' => 'The selected SOP workflow has no approval steps.',
                ]);
            }

            $submissionUuid = (string) Str::uuid();

            foreach ($steps as $step) {
                SopTemplateApprovalInstance::query()->create([
                    'instance_uuid' => (string) Str::uuid(),
                    'submission_uuid' => $submissionUuid,
                    'sop_template_version_id' => $version->id,
                    'workflow_id' => $workflow->id,
                    'workflow_step_id' => $step->id,
                    'decision_code' => 'pending',
                ]);
            }

            $from = $version->approval_status;
            $occurredAt = CarbonImmutable::now();

            $version->update([
                'approval_status' => TemplateApprovalStatus::Submitted,
                'submitted_by' => $actor->id,
                'submitted_at' => $occurredAt,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            SopTemplateApprovalEvent::query()->create([
                'sop_template_version_id' => $version->id,
                'event_uuid' => (string) Str::uuid(),
                'from_status' => $from,
                'to_status' => TemplateApprovalStatus::Submitted,
                'actor_id' => $actor->id,
                'reason' => $reason,
                'occurred_at' => $occurredAt,
            ]);

            return $version->refresh();
        });
    }
}
