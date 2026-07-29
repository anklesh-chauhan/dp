<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Domain\Shared\Services\AuditLogService;
use App\Enums\ProductModule;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\SopAuditLog;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TemplatePublisherService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureVerifier $electronicSignatureVerifier,
    ) {}

    /**
     * @throws ValidationException
     */
    public function publish(DocumentTemplate $template, int $userId, ?string $changeReason = null): DocumentTemplateVersion
    {
        $this->moduleManager->ensureEnabled(ProductModule::DMS);

        return DB::transaction(function () use ($template, $userId, $changeReason): DocumentTemplateVersion {
            $template = DocumentTemplate::query()->lockForUpdate()->findOrFail($template->id);

            if ($template->isInRetentionLifecycle()) {
                throw ValidationException::withMessages(['template' => 'Templates in the retention lifecycle cannot be published.']);
            }

            $draftVersion = $template->versions()
                ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::DRAFT))
                ->with(['sections', 'variables'])
                ->orderByDesc('version')
                ->first();

            if ($draftVersion === null) {
                throw ValidationException::withMessages(['version' => 'Create a draft template version before publishing.']);
            }

            if ($draftVersion->approval_status !== TemplateApprovalStatus::Approved) {
                throw ValidationException::withMessages([
                    'approval_status' => 'The draft template version must be independently reviewed and approved before publishing.',
                ]);
            }

            $latestSubmissionUuid = $draftVersion->approvalInstances()->latest('id')->value('submission_uuid');
            $instances = DocumentTemplateApprovalInstance::query()
                ->where('submission_uuid', $latestSubmissionUuid)
                ->with('workflowStep')
                ->get();
            $mandatoryInstances = $instances->filter(
                fn (DocumentTemplateApprovalInstance $instance): bool => $instance->workflowStep->is_mandatory,
            );
            $requiredInstances = $mandatoryInstances->isEmpty() ? $instances : $mandatoryInstances;

            if (
                $requiredInstances->isEmpty()
                || ! $requiredInstances->every(
                    fn (DocumentTemplateApprovalInstance $instance): bool => $instance->decision_code === ApprovalDecisionCode::APPROVED->value
                        && $this->electronicSignatureVerifier->isValid($instance),
                )
            ) {
                throw ValidationException::withMessages([
                    'approval_status' => 'Every mandatory workflow step must have a valid signed approval before publishing.',
                ]);
            }

            $publisher = User::query()->find($userId);

            if ($publisher === null || ! $publisher->can('Publish:DocumentTemplate')) {
                throw new AuthorizationException('You do not have permission to publish document templates.');
            }

            $previousVersion = $template->current_version;
            $previousPublishedVersion = $template->versions()
                ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::PUBLISHED))
                ->orderByDesc('version')
                ->first(['id', 'version']);
            $previousPublishedVersionId = $previousPublishedVersion?->id;
            $nextVersion = ($previousPublishedVersion?->version ?? 0) + 1;

            if ($draftVersion->version !== $nextVersion) {
                $draftVersion->update(['version' => $nextVersion]);
            }

            $publishedStatusId = TemplateStatus::idFor(TemplateStatus::PUBLISHED);

            $draftVersion->update([
                'template_status_id' => $publishedStatusId,
                'change_reason' => $changeReason ?? $draftVersion->change_reason,
            ]);

            $template->update([
                'template_status_id' => $publishedStatusId,
                'current_version' => $nextVersion,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_VERSION_PUBLISHED,
                oldValues: [
                    'current_version' => $previousVersion,
                    'template_version_id' => $previousPublishedVersionId,
                ],
                newValues: [
                    'template_id' => $template->id,
                    'template_version_id' => $draftVersion->id,
                    'version' => $nextVersion,
                    'change_reason' => $draftVersion->change_reason,
                ],
                userId: $userId,
                template: $template,
            );

            return $draftVersion->refresh();
        });
    }
}
