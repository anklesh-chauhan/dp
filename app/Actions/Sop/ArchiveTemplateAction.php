<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopAuditLog;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use App\Services\Sop\AuditLogService;
use Illuminate\Support\Facades\DB;

class ArchiveTemplateAction
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function execute(SopTemplate $template, int $userId): SopTemplate
    {
        return DB::transaction(function () use ($template, $userId): SopTemplate {
            $oldValues = $template->only(['template_status_id']);

            $archivedStatusId = TemplateStatus::idFor(TemplateStatus::ARCHIVED);

            $template->update(['template_status_id' => $archivedStatusId]);
            $template->versions()
                ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::DRAFT))
                ->update(['template_status_id' => $archivedStatusId]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_ARCHIVED,
                oldValues: $oldValues,
                newValues: [
                    'template_id' => $template->id,
                    'status' => TemplateStatus::ARCHIVED,
                ],
                userId: $userId,
                template: $template,
            );

            return $template->refresh();
        });
    }
}
