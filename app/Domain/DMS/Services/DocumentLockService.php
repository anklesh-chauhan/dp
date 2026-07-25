<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\Sop\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentLockService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function lockDocument(SopDocument $document, User $user): SopDocument
    {
        $this->ensureDocumentIsLockable($document, $user);

        return DB::transaction(function () use ($document, $user): SopDocument {
            $document->update([
                'locked_by' => $user->id,
                'locked_at' => now(),
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_LOCKED,
                newValues: ['locked_by' => $user->id],
                userId: $user->id,
                document: $document,
            );

            return $document->refresh();
        });
    }

    public function unlockDocument(SopDocument $document, User $user, bool $force = false): SopDocument
    {
        if (! $document->isLocked()) {
            return $document;
        }

        if (! $force && $document->isLockedByOther($user)) {
            throw ValidationException::withMessages([
                'lock' => 'This document is locked by another user.',
            ]);
        }

        return DB::transaction(function () use ($document, $user): SopDocument {
            $document->update([
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_UNLOCKED,
                newValues: ['unlocked_by' => $user->id],
                userId: $user->id,
                document: $document,
            );

            return $document->refresh();
        });
    }

    public function lockTemplate(SopTemplate $template, User $user): SopTemplate
    {
        if (! $template->templateStatus?->hasCode(TemplateStatus::DRAFT)) {
            throw ValidationException::withMessages([
                'lock' => 'Only draft templates can be locked for editing.',
            ]);
        }

        if ($template->isLockedByOther($user)) {
            throw ValidationException::withMessages([
                'lock' => 'This template is locked by another user.',
            ]);
        }

        return DB::transaction(function () use ($template, $user): SopTemplate {
            $template->update([
                'locked_by' => $user->id,
                'locked_at' => now(),
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_LOCKED,
                newValues: ['locked_by' => $user->id],
                userId: $user->id,
                template: $template,
            );

            return $template->refresh();
        });
    }

    public function unlockTemplate(SopTemplate $template, User $user, bool $force = false): SopTemplate
    {
        if (! $template->isLocked()) {
            return $template;
        }

        if (! $force && $template->isLockedByOther($user)) {
            throw ValidationException::withMessages([
                'lock' => 'This template is locked by another user.',
            ]);
        }

        return DB::transaction(function () use ($template, $user): SopTemplate {
            $template->update([
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_UNLOCKED,
                newValues: ['unlocked_by' => $user->id],
                userId: $user->id,
                template: $template,
            );

            return $template->refresh();
        });
    }

    private function ensureDocumentIsLockable(SopDocument $document, User $user): void
    {
        if (! $document->isEditable()) {
            throw ValidationException::withMessages([
                'lock' => 'This document cannot be locked because it is not in an editable state.',
            ]);
        }

        if ($document->isLockedByOther($user)) {
            throw ValidationException::withMessages([
                'lock' => 'This document is locked by another user.',
            ]);
        }
    }
}
