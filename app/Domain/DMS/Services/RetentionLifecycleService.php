<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetentionLifecycleService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function markDocumentObsolete(SopDocument $document, User $user, ?string $reason = null): SopDocument
    {
        $this->ensureDocumentStatus(
            $document,
            [DocumentStatus::EFFECTIVE, DocumentStatus::APPROVED],
            'Only effective or approved documents can be marked obsolete.',
        );

        return $this->transitionDocument(
            $document,
            $user,
            DocumentStatus::OBSOLETE,
            SopAuditLog::ACTION_MARKED_OBSOLETE,
            $reason,
        );
    }

    public function archiveDocument(SopDocument $document, User $user, ?string $reason = null): SopDocument
    {
        $this->ensureDocumentStatus(
            $document,
            [DocumentStatus::SUPERSEDED, DocumentStatus::OBSOLETE],
            'Only superseded or obsolete documents can be archived.',
        );

        return $this->transitionDocument(
            $document,
            $user,
            DocumentStatus::ARCHIVED,
            SopAuditLog::ACTION_ARCHIVED,
            $reason,
        );
    }

    public function completeDocumentRetention(SopDocument $document, User $user, ?string $reason = null): SopDocument
    {
        $this->ensureDocumentStatus(
            $document,
            [DocumentStatus::ARCHIVED],
            'Only archived documents can be marked as retention completed.',
        );

        return $this->transitionDocument(
            $document,
            $user,
            DocumentStatus::RETENTION_COMPLETED,
            SopAuditLog::ACTION_RETENTION_COMPLETED,
            $reason,
        );
    }

    public function destroyDocument(SopDocument $document, User $user, string $reason): SopDocument
    {
        $this->ensureDocumentStatus(
            $document,
            [DocumentStatus::RETENTION_COMPLETED],
            'Only documents with completed retention can be destroyed.',
        );

        return $this->transitionDocument(
            $document,
            $user,
            DocumentStatus::DESTROYED,
            SopAuditLog::ACTION_DESTROYED,
            $reason,
        );
    }

    public function markTemplateObsolete(SopTemplate $template, User $user, ?string $reason = null): SopTemplate
    {
        $this->ensureTemplateStatus(
            $template,
            [TemplateStatus::PUBLISHED],
            'Only published templates can be marked obsolete.',
        );

        return $this->transitionTemplate(
            $template,
            $user,
            TemplateStatus::OBSOLETE,
            SopAuditLog::ACTION_MARKED_OBSOLETE,
            $reason,
        );
    }

    public function archiveTemplate(SopTemplate $template, User $user, ?string $reason = null): SopTemplate
    {
        $this->ensureTemplateStatus(
            $template,
            [TemplateStatus::OBSOLETE],
            'Only obsolete templates can be archived.',
        );

        return DB::transaction(function () use ($template, $user, $reason): SopTemplate {
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
                    'reason' => $reason,
                ],
                userId: $user->id,
                template: $template,
            );

            return $template->refresh();
        });
    }

    public function completeTemplateRetention(SopTemplate $template, User $user, ?string $reason = null): SopTemplate
    {
        $this->ensureTemplateStatus(
            $template,
            [TemplateStatus::ARCHIVED],
            'Only archived templates can be marked as retention completed.',
        );

        return $this->transitionTemplate(
            $template,
            $user,
            TemplateStatus::RETENTION_COMPLETED,
            SopAuditLog::ACTION_RETENTION_COMPLETED,
            $reason,
        );
    }

    public function destroyTemplate(SopTemplate $template, User $user, string $reason): SopTemplate
    {
        $this->ensureTemplateStatus(
            $template,
            [TemplateStatus::RETENTION_COMPLETED],
            'Only templates with completed retention can be destroyed.',
        );

        return $this->transitionTemplate(
            $template,
            $user,
            TemplateStatus::DESTROYED,
            SopAuditLog::ACTION_DESTROYED,
            $reason,
        );
    }

    /**
     * @param  list<string>  $allowedCodes
     */
    private function ensureDocumentStatus(SopDocument $document, array $allowedCodes, string $message): void
    {
        $currentCode = $document->documentStatus?->code;

        if ($currentCode === null || ! in_array($currentCode, $allowedCodes, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    /**
     * @param  list<string>  $allowedCodes
     */
    private function ensureTemplateStatus(SopTemplate $template, array $allowedCodes, string $message): void
    {
        $currentCode = $template->templateStatus?->code;

        if ($currentCode === null || ! in_array($currentCode, $allowedCodes, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function transitionDocument(
        SopDocument $document,
        User $user,
        string $statusCode,
        string $auditAction,
        ?string $reason,
    ): SopDocument {
        return DB::transaction(function () use ($document, $user, $statusCode, $auditAction, $reason): SopDocument {
            $oldValues = $document->only(['document_status_id']);

            $document->update([
                'document_status_id' => DocumentStatus::idFor($statusCode),
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $this->auditLogService->log(
                action: $auditAction,
                oldValues: $oldValues,
                newValues: [
                    'document_id' => $document->id,
                    'status' => $statusCode,
                    'reason' => $reason,
                ],
                userId: $user->id,
                document: $document,
            );

            return $document->refresh();
        });
    }

    private function transitionTemplate(
        SopTemplate $template,
        User $user,
        string $statusCode,
        string $auditAction,
        ?string $reason,
    ): SopTemplate {
        return DB::transaction(function () use ($template, $user, $statusCode, $auditAction, $reason): SopTemplate {
            $oldValues = $template->only(['template_status_id']);

            $template->update(['template_status_id' => TemplateStatus::idFor($statusCode)]);

            $this->auditLogService->log(
                action: $auditAction,
                oldValues: $oldValues,
                newValues: [
                    'template_id' => $template->id,
                    'status' => $statusCode,
                    'reason' => $reason,
                ],
                userId: $user->id,
                template: $template,
            );

            return $template->refresh();
        });
    }
}
