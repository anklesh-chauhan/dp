<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Enums\ProductModule;
use App\Exceptions\WorkflowException;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\SopRole;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TemplateApprovalDecisionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    public function decide(
        DocumentTemplateApprovalInstance $instance,
        User $actor,
        ApprovalDecisionCode $decision,
        string $comments,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): DocumentTemplateApprovalInstance {
        $this->moduleManager->ensureEnabled(ProductModule::DMS);

        if (! in_array($decision, [
            ApprovalDecisionCode::APPROVED,
            ApprovalDecisionCode::REJECTED,
            ApprovalDecisionCode::RETURNED,
        ], true)) {
            throw new WorkflowException(message: 'Unsupported template approval decision.');
        }

        $comments = trim($comments);

        if ($comments === '') {
            throw ValidationException::withMessages(['comments' => 'A decision reason is required.']);
        }

        return DB::transaction(function () use ($instance, $actor, $decision, $comments, $ipAddress, $userAgent): DocumentTemplateApprovalInstance {
            $instance = DocumentTemplateApprovalInstance::query()
                ->with(['templateVersion.template', 'workflowStep.role', 'workflowStep.department'])
                ->lockForUpdate()
                ->findOrFail($instance->id);

            $this->authorize($instance, $actor);

            $decidedAt = now();
            $signatureHash = $this->electronicSignatureHasher->hashFor(
                recordKey: $instance->instance_uuid,
                meaning: $decision->value,
                signerId: $actor->id,
                signedAt: $decidedAt,
                reason: $comments,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $instance->update([
                'decision_code' => $decision->value,
                'decided_by' => $actor->id,
                'comments' => $comments,
                'decided_at' => $decidedAt,
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $ipAddress,
                'signature_user_agent' => $userAgent,
            ]);

            match ($decision) {
                ApprovalDecisionCode::APPROVED => $this->applyApprovedOutcome($instance, $actor),
                ApprovalDecisionCode::REJECTED => $this->applyTerminalOutcome(
                    $instance,
                    TemplateApprovalStatus::Rejected,
                ),
                ApprovalDecisionCode::RETURNED => $this->applyTerminalOutcome(
                    $instance,
                    TemplateApprovalStatus::Draft,
                ),
                default => null,
            };

            return $instance->refresh();
        });
    }

    public function canDecide(DocumentTemplateApprovalInstance $instance, User $actor): bool
    {
        try {
            $this->authorize($instance, $actor);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function authorize(DocumentTemplateApprovalInstance $instance, User $actor): void
    {
        $instance->loadMissing([
            'templateVersion.template',
            'workflowStep.role',
            'workflowStep.department',
        ]);
        $version = $instance->templateVersion;

        if ($version->approval_status !== TemplateApprovalStatus::Submitted) {
            throw new WorkflowException(message: 'This template approval is no longer available.');
        }

        if ($instance->decision_code !== ApprovalDecisionCode::PENDING->value) {
            throw new WorkflowException(message: 'This template approval step has already been decided.');
        }

        $latestSubmissionUuid = $version->approvalInstances()->latest('id')->value('submission_uuid');

        if ($instance->submission_uuid !== $latestSubmissionUuid) {
            throw new WorkflowException(message: 'This template approval belongs to an earlier submission cycle.');
        }

        if (! $actor->can('Decide:DocumentTemplateApproval')) {
            throw new WorkflowException(message: 'You do not have permission to decide template approvals.');
        }

        if (! $actor->hasRole($instance->workflowStep->role)) {
            throw new WorkflowException(
                message: "Only users with the '{$instance->workflowStep->role->name}' role can decide this step.",
            );
        }

        if (in_array($actor->id, [
            $version->created_by,
            $version->submitted_by,
            $version->template->created_by,
        ], true)) {
            throw new WorkflowException(message: 'The template author or submitter cannot decide its approval steps.');
        }

        if ($version->approvalInstances()
            ->where('submission_uuid', $instance->submission_uuid)
            ->where('decided_by', $actor->id)
            ->exists()) {
            throw new WorkflowException(message: 'Every template approval step must be decided by a different user.');
        }

        if ($this->hasPreviousMandatoryStepPending($instance)) {
            throw new WorkflowException(message: 'A previous mandatory template approval step is still pending.');
        }

        $requiredDepartmentId = $instance->workflowStep->resolveRequiredDepartmentId(
            $version->approvalSubjectDepartmentId(),
        );

        if (
            ! $actor->hasRole(SopRole::ADMINISTRATOR)
            && $actor->department_id !== null
            && $requiredDepartmentId !== null
            && $actor->department_id !== $requiredDepartmentId
        ) {
            throw new WorkflowException(message: 'You can only decide template approvals for your own department.');
        }
    }

    private function hasPreviousMandatoryStepPending(DocumentTemplateApprovalInstance $instance): bool
    {
        return DocumentTemplateApprovalInstance::query()
            ->where('submission_uuid', $instance->submission_uuid)
            ->whereHas('workflowStep', fn ($query) => $query
                ->where('step_no', '<', $instance->workflowStep->step_no)
                ->where('is_mandatory', true))
            ->where('decision_code', '!=', ApprovalDecisionCode::APPROVED->value)
            ->exists();
    }

    private function applyApprovedOutcome(DocumentTemplateApprovalInstance $instance, User $actor): void
    {
        $instances = DocumentTemplateApprovalInstance::query()
            ->where('submission_uuid', $instance->submission_uuid)
            ->with('workflowStep')
            ->get();
        $mandatory = $instances->filter(
            fn (DocumentTemplateApprovalInstance $approval): bool => $approval->workflowStep->is_mandatory,
        );
        $required = $mandatory->isEmpty() ? $instances : $mandatory;

        if (! $required->every(
            fn (DocumentTemplateApprovalInstance $approval): bool => $approval->decision_code === ApprovalDecisionCode::APPROVED->value,
        )) {
            return;
        }

        $this->markRemainingNotRequired($instance);
        $instance->templateVersion->update([
            'approval_status' => TemplateApprovalStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);
    }

    private function applyTerminalOutcome(
        DocumentTemplateApprovalInstance $instance,
        TemplateApprovalStatus $status,
    ): void {
        $this->markRemainingNotRequired($instance);
        $instance->templateVersion->update([
            'approval_status' => $status,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    private function markRemainingNotRequired(DocumentTemplateApprovalInstance $instance): void
    {
        DocumentTemplateApprovalInstance::query()
            ->where('submission_uuid', $instance->submission_uuid)
            ->whereKeyNot($instance->id)
            ->where('decision_code', ApprovalDecisionCode::PENDING->value)
            ->update(['decision_code' => 'not_required']);
    }
}
