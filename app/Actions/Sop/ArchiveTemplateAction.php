<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Enums\TemplateStatus;
use App\Models\SopAuditLog;
use App\Models\SopTemplate;
use App\Services\Sop\AuditLogService;
use Illuminate\Support\Facades\DB;

class ArchiveTemplateAction
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function execute(SopTemplate $template, int $userId): SopTemplate
    {
        return DB::transaction(function () use ($template, $userId): SopTemplate {
            $oldValues = $template->only(['status']);

            $template->update(['status' => TemplateStatus::Archived]);
            $template->versions()->where('status', TemplateStatus::Draft)->update(['status' => TemplateStatus::Archived]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_ARCHIVED,
                oldValues: $oldValues,
                newValues: [
                    'template_id' => $template->id,
                    'status' => TemplateStatus::Archived->value,
                ],
                userId: $userId,
                template: $template,
            );

            return $template->refresh();
        });
    }
}
