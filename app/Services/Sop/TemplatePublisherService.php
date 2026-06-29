<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Enums\TemplateStatus;
use App\Models\SopAuditLog;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
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

            if ($template->status === TemplateStatus::Archived) {
                throw ValidationException::withMessages(['template' => 'Archived templates cannot be published.']);
            }

            $draftVersion = $template->versions()
                ->where('status', TemplateStatus::Draft)
                ->with(['sections', 'variables'])
                ->orderByDesc('version')
                ->first();

            if ($draftVersion === null) {
                throw ValidationException::withMessages(['version' => 'Create a draft template version before publishing.']);
            }

            $nextVersion = max($template->current_version + 1, $draftVersion->version);
            $previousVersion = $template->current_version;
            $previousPublishedVersionId = $template->versions()
                ->where('status', TemplateStatus::Published)
                ->orderByDesc('version')
                ->value('id');

            if ($draftVersion->version !== $nextVersion) {
                $draftVersion->update(['version' => $nextVersion]);
            }

            $draftVersion->update([
                'status' => TemplateStatus::Published,
                'change_reason' => $changeReason ?? $draftVersion->change_reason,
            ]);

            $template->update([
                'status' => TemplateStatus::Published,
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
