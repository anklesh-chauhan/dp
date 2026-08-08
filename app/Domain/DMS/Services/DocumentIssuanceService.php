<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\IssuanceStatus;
use App\Models\SopAuditLog;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentIssuanceService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly DocumentNumberGeneratorService $documentNumberGeneratorService,
        private readonly DocumentExecutionService $documentExecutionService,
    ) {}

    /**
     * @param  array{
     *     issued_to_user_id?: int|null,
     *     issued_to_department_id?: int|null,
     *     issued_to_location?: string|null,
     *     notes?: string|null,
     *     issuance_type?: string|null,
     *     batch_number?: string|null,
     *     product_name?: string|null,
     *     log_frequency?: string|null,
     *     log_period_start?: string|null,
     *     log_period_end?: string|null,
     *     supervisor_id?: int|null
     * }  $data
     */
    public function issue(ControlledDocument $document, User $issuer, array $data = []): DocumentIssuance
    {
        if (! $document->canBeIssued()) {
            throw ValidationException::withMessages([
                'issuance' => $document->requiresSopReference() && $document->referencedSopIsUnavailable()
                    ? 'Controlled copies cannot be issued when the referenced SOP is not effective or has been archived.'
                    : 'Only effective issuable documents with a valid SOP reference can be issued.',
            ]);
        }

        try {
            return DB::transaction(function () use ($document, $issuer, $data): DocumentIssuance {
                $lockedDocument = ControlledDocument::query()
                    ->with(['documentStatus', 'documentType', 'referencedSop.documentStatus'])
                    ->lockForUpdate()
                    ->findOrFail($document->getKey());

                if (! $lockedDocument->canBeIssued()) {
                    throw ValidationException::withMessages([
                        'issuance' => 'The document is no longer eligible for controlled-copy issuance.',
                    ]);
                }

                $copyNumber = $this->documentNumberGeneratorService->nextCopyNumber($lockedDocument);
                $issuanceNumber = $this->documentNumberGeneratorService->generateIssuanceNumber($lockedDocument, $copyNumber);
                $watermarkCode = $this->documentNumberGeneratorService->generateWatermarkCode($lockedDocument, $copyNumber);
                $issuanceType = $data['issuance_type'] ?? ($lockedDocument->documentType?->requiresExecutionRecord()
                    ? DocumentIssuance::TYPE_EXECUTION
                    : DocumentIssuance::TYPE_REFERENCE);

                if (! in_array($issuanceType, [DocumentIssuance::TYPE_REFERENCE, DocumentIssuance::TYPE_EXECUTION], true)) {
                    throw ValidationException::withMessages([
                        'issuance_type' => 'Select either a read-only reference copy or a writable execution record.',
                    ]);
                }

                if ($issuanceType === DocumentIssuance::TYPE_EXECUTION && ! $lockedDocument->documentType?->requiresExecutionRecord()) {
                    throw ValidationException::withMessages([
                        'issuance_type' => 'This master document is not configured as a writable GMP record.',
                    ]);
                }

                $issuance = DocumentIssuance::query()->create([
                    'document_id' => $lockedDocument->id,
                    'copy_number' => $copyNumber,
                    'issuance_number' => $issuanceNumber,
                    'issuance_type' => $issuanceType,
                    'issued_to_user_id' => $data['issued_to_user_id'] ?? null,
                    'issued_to_department_id' => $data['issued_to_department_id'] ?? null,
                    'issued_to_location' => $data['issued_to_location'] ?? null,
                    'issued_by' => $issuer->id,
                    'issued_at' => now(),
                    'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::ACTIVE),
                    'watermark_code' => $watermarkCode,
                    'notes' => $data['notes'] ?? null,
                ]);

                if ($issuance->isExecution()) {
                    $this->documentExecutionService->initialize($issuance, $data);
                }

                $this->auditLogService->log(
                    action: SopAuditLog::ACTION_ISSUED,
                    newValues: [
                        'issuance_id' => $issuance->id,
                        'issuance_number' => $issuance->issuance_number,
                        'copy_number' => $copyNumber,
                        'issuance_type' => $issuance->issuance_type,
                        'issued_to_user_id' => $issuance->issued_to_user_id,
                        'issued_to_department_id' => $issuance->issued_to_department_id,
                        'issued_to_location' => $issuance->issued_to_location,
                    ],
                    userId: $issuer->id,
                    document: $lockedDocument,
                );

                return $issuance;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'issuance' => 'Another controlled copy was issued at the same time. Please submit again to receive the next copy number.',
            ]);
        }
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
