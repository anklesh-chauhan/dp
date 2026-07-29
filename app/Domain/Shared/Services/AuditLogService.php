<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\ControlledDocument;
use App\Models\DocumentTemplate;
use App\Models\SopAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?ControlledDocument $document = null,
        ?DocumentTemplate $template = null,
    ): SopAuditLog {
        return SopAuditLog::query()->create([
            'document_id' => $document?->id,
            'document_template_id' => $template?->id ?? $document?->template_id,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
