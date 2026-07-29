<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Models\ControlledDocument;
use App\Models\DocumentTemplate;
use App\Models\SopAuditLog;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentLockService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function lockDocument(ControlledDocument $document, User $user): ControlledDocument
    {
        $this->ensureDocumentIsLockable($document, $user);

        return DB::transaction(function () use ($document, $user): ControlledDocument {
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

    public function unlockDocument(ControlledDocument $document, User $user, bool $force = false): ControlledDocument
    {
        if (! $document->isLocked()) {
            return $document;
        }

        if (! $force && $document->isLockedByOther($user)) {
            throw ValidationException::withMessages([
                'lock' => 'This document is locked by another user.',
            ]);
        }

        return DB::transaction(function () use ($document, $user): ControlledDocument {
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

    public function lockTemplate(DocumentTemplate $template, User $user): DocumentTemplate
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

        return DB::transaction(function () use ($template, $user): DocumentTemplate {
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

    public function unlockTemplate(DocumentTemplate $template, User $user, bool $force = false): DocumentTemplate
    {
        if (! $template->isLocked()) {
            return $template;
        }

        if (! $force && $template->isLockedByOther($user)) {
            throw ValidationException::withMessages([
                'lock' => 'This template is locked by another user.',
            ]);
        }

        return DB::transaction(function () use ($template, $user): DocumentTemplate {
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

    private function ensureDocumentIsLockable(ControlledDocument $document, User $user): void
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
