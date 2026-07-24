<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentRevisionService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @throws ValidationException
     */
    public function create(SopDocument $sourceDocument, User $user, string $reason): SopDocument
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['revision_reason' => 'A revision reason is required.']);
        }

        return DB::transaction(function () use ($sourceDocument, $user, $reason): SopDocument {
            $source = SopDocument::query()
                ->with(['documentStatus', 'sections', 'variables', 'regulationTags'])
                ->lockForUpdate()
                ->findOrFail($sourceDocument->id);

            if (! in_array($source->documentStatus?->code, [
                DocumentStatus::APPROVED,
                DocumentStatus::EFFECTIVE,
                DocumentStatus::OBSOLETE,
            ], true)) {
                throw ValidationException::withMessages([
                    'document' => 'Only approved, effective, or obsolete documents can be revised.',
                ]);
            }

            $documentSeriesId = $source->document_series_id ?? (string) Str::uuid();

            if ($source->document_series_id === null) {
                $source->update(['document_series_id' => $documentSeriesId]);
            }

            $latestVersion = SopDocument::query()
                ->where('document_series_id', $documentSeriesId)
                ->lockForUpdate()
                ->max('version');

            $existingDraft = SopDocument::query()
                ->where('document_series_id', $documentSeriesId)
                ->whereHas('documentStatus', fn (Builder $query): Builder => $query->where('code', DocumentStatus::DRAFT))
                ->exists();

            if ($existingDraft) {
                throw ValidationException::withMessages([
                    'document' => 'This document already has a draft revision.',
                ]);
            }

            $revision = $source->replicate([
                'document_status_id',
                'effective_date',
                'review_date',
                'locked_by',
                'locked_at',
            ]);
            $revision->fill([
                'document_series_id' => $documentSeriesId,
                'supersedes_document_id' => $source->id,
                'version' => ((int) $latestVersion) + 1,
                'revision_reason' => $reason,
                'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
                'effective_date' => null,
                'review_date' => null,
                'created_by' => $user->id,
                'locked_by' => null,
                'locked_at' => null,
            ]);
            $revision->save();

            foreach ($source->sections as $section) {
                $revision->sections()->create($section->only(['title', 'section_order', 'content']));
            }

            foreach ($source->variables as $variable) {
                $revision->variables()->create($variable->only(['variable_name', 'value']));
            }

            $revision->regulationTags()->sync($source->regulationTags->modelKeys());

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_DOCUMENT_REVISION_CREATED,
                oldValues: [
                    'document_id' => $source->id,
                    'document_version' => $source->version,
                    'template_version_id' => $source->template_version_id,
                ],
                newValues: [
                    'document_id' => $revision->id,
                    'document_version' => $revision->version,
                    'template_version_id' => $revision->template_version_id,
                    'revision_reason' => $reason,
                ],
                userId: $user->id,
                document: $revision,
            );

            return $revision->refresh()->load([
                'templateVersion',
                'documentStatus',
                'sections',
                'variables',
                'regulationTags',
                'supersedesDocument',
            ]);
        });
    }
}
