<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Models\SopAuditLog;
use App\Models\SopDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(?SopDocument $document, string $action, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null): SopAuditLog
    {
        return SopAuditLog::query()->create([
            'document_id' => $document?->id,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
