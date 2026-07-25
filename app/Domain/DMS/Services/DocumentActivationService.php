<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentActivationService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function activate(SopDocument $document, User $user): SopDocument
    {
        return DB::transaction(function () use ($document, $user): SopDocument {
            $effectiveStatusId = DocumentStatus::idFor(DocumentStatus::EFFECTIVE);
            $supersededStatusId = DocumentStatus::idFor(DocumentStatus::SUPERSEDED);

            $activatingDocument = SopDocument::query()
                ->lockForUpdate()
                ->findOrFail($document->id);

            $priorEffectiveVersions = SopDocument::query()
                ->where('document_series_id', $activatingDocument->document_series_id)
                ->whereKeyNot($activatingDocument->id)
                ->where('version', '<', $activatingDocument->version)
                ->where('document_status_id', $effectiveStatusId)
                ->lockForUpdate()
                ->get();

            $activatingDocument->update(['document_status_id' => $effectiveStatusId]);

            foreach ($priorEffectiveVersions as $priorVersion) {
                $priorVersion->update([
                    'document_status_id' => $supersededStatusId,
                    'locked_by' => null,
                    'locked_at' => null,
                ]);

                $this->auditLogService->log(
                    action: SopAuditLog::ACTION_SUPERSEDED,
                    oldValues: [
                        'status' => DocumentStatus::EFFECTIVE,
                        'document_version' => $priorVersion->version,
                    ],
                    newValues: [
                        'status' => DocumentStatus::SUPERSEDED,
                        'superseded_by_document_id' => $activatingDocument->id,
                        'superseded_by_version' => $activatingDocument->version,
                    ],
                    userId: $user->id,
                    document: $priorVersion,
                );
            }

            return $activatingDocument->refresh();
        });
    }
}
