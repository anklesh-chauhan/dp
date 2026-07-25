<?php

namespace App\Domain\DMS\Services;

use App\Models\DocumentIssuance;
use App\Models\IssuanceStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\User;
use App\Services\Sop\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentIssuanceService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly DocumentNumberGeneratorService $documentNumberGeneratorService,
    ) {}

    /**
     * @param  array{
     *     issued_to_user_id?: int|null,
     *     issued_to_department_id?: int|null,
     *     issued_to_location?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function issue(SopDocument $document, User $issuer, array $data = []): DocumentIssuance
    {
        if (! $document->canBeIssued()) {
            throw ValidationException::withMessages([
                'issuance' => $document->requiresSopReference() && $document->referencedSopIsUnavailable()
                    ? 'Controlled copies cannot be issued when the referenced SOP is not effective or has been archived.'
                    : 'Only effective log documents with a valid SOP reference can be issued.',
            ]);
        }

        return DB::transaction(function () use ($document, $issuer, $data): DocumentIssuance {
            $copyNumber = $this->documentNumberGeneratorService->nextCopyNumber($document);
            $issuanceNumber = $this->documentNumberGeneratorService->generateIssuanceNumber($document, $copyNumber);
            $watermarkCode = $this->documentNumberGeneratorService->generateWatermarkCode($document, $copyNumber);

            $issuance = DocumentIssuance::query()->create([
                'document_id' => $document->id,
                'copy_number' => $copyNumber,
                'issuance_number' => $issuanceNumber,
                'issued_to_user_id' => $data['issued_to_user_id'] ?? null,
                'issued_to_department_id' => $data['issued_to_department_id'] ?? null,
                'issued_to_location' => $data['issued_to_location'] ?? null,
                'issued_by' => $issuer->id,
                'issued_at' => now(),
                'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::ACTIVE),
                'watermark_code' => $watermarkCode,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_ISSUED,
                newValues: [
                    'issuance_id' => $issuance->id,
                    'issuance_number' => $issuance->issuance_number,
                    'copy_number' => $copyNumber,
                    'issued_to_user_id' => $issuance->issued_to_user_id,
                    'issued_to_department_id' => $issuance->issued_to_department_id,
                    'issued_to_location' => $issuance->issued_to_location,
                ],
                userId: $issuer->id,
                document: $document,
            );

            return $issuance;
        });
    }

    public function recall(DocumentIssuance $issuance, User $user, string $reason): DocumentIssuance
    {
        if (! $issuance->isActive()) {
            throw ValidationException::withMessages([
                'issuance' => 'Only active controlled copies can be recalled.',
            ]);
        }

        return DB::transaction(function () use ($issuance, $user, $reason): DocumentIssuance {
            $issuance->update([
                'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::RECALLED),
                'recalled_by' => $user->id,
                'recalled_at' => now(),
                'recall_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_RECALLED,
                newValues: [
                    'issuance_id' => $issuance->id,
                    'issuance_number' => $issuance->issuance_number,
                    'recall_reason' => $reason,
                ],
                userId: $user->id,
                document: $issuance->document,
            );

            return $issuance->refresh();
        });
    }

    public function destroyCopy(DocumentIssuance $issuance, User $user, string $reason): DocumentIssuance
    {
        if ($issuance->issuanceStatus?->hasCode(IssuanceStatus::DESTROYED)) {
            throw ValidationException::withMessages([
                'issuance' => 'This controlled copy has already been destroyed.',
            ]);
        }

        return DB::transaction(function () use ($issuance, $user, $reason): DocumentIssuance {
            $issuance->update([
                'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::DESTROYED),
                'destroyed_by' => $user->id,
                'destroyed_at' => now(),
                'destroy_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_COPY_DESTROYED,
                newValues: [
                    'issuance_id' => $issuance->id,
                    'issuance_number' => $issuance->issuance_number,
                    'destroy_reason' => $reason,
                ],
                userId: $user->id,
                document: $issuance->document,
            );

            return $issuance->refresh();
        });
    }
}
