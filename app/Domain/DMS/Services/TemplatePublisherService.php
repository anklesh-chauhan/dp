<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Models\SopAuditLog;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TemplatePublisherService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @throws ValidationException
     */
    public function publish(SopTemplate $template, int $userId, ?string $changeReason = null): SopTemplateVersion
    {
        return DB::transaction(function () use ($template, $userId, $changeReason): SopTemplateVersion {
            $template = SopTemplate::query()->lockForUpdate()->findOrFail($template->id);

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

            if ($template->current_version === 0) {
                $nextVersion = 1;
            } else {
                $nextVersion = $template->current_version + 1;
            }

            $previousVersion = $template->current_version;
            $previousPublishedVersionId = $template->versions()
                ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::PUBLISHED))
                ->orderByDesc('version')
                ->value('id');

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
