<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use App\Models\IssuanceStatus;
use App\Models\SopAuditLog;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
     *     copy_count?: int|string|null,
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
        $issuance = $this->issueCopies($document, $issuer, $data)->last();

        assert($issuance instanceof DocumentIssuance);

        return $issuance;
    }

    /**
     * @param  array{
     *     issued_to_user_id?: int|null,
     *     issued_to_department_id?: int|null,
     *     issued_to_location?: string|null,
     *     notes?: string|null,
     *     issuance_type?: string|null,
     *     copy_count?: int|string|null,
     *     batch_number?: string|null,
     *     product_name?: string|null,
     *     log_frequency?: string|null,
     *     log_period_start?: string|null,
     *     log_period_end?: string|null,
     *     supervisor_id?: int|null
     * }  $data
     * @return Collection<int, DocumentIssuance>
     */
    public function issueCopies(ControlledDocument $document, User $issuer, array $data = []): Collection
    {
        $copyCount = $this->copyCount($data);

        if (! $document->canBeIssued()) {
            throw ValidationException::withMessages([
                'issuance' => $document->requiresSopReference() && $document->referencedSopIsUnavailable()
                    ? 'Controlled copies cannot be issued when the referenced SOP is not effective or has been archived.'
                    : 'Only effective issuable documents with a valid SOP reference can be issued.',
            ]);
        }

        if (blank($data['issued_to_user_id'] ?? null) && blank($data['issued_to_department_id'] ?? null)) {
            throw ValidationException::withMessages([
                'issued_to_user_id' => 'Specify the user or department receiving this controlled copy.',
            ]);
        }

        try {
            return DB::transaction(function () use ($document, $issuer, $data, $copyCount): Collection {
                $lockedDocument = ControlledDocument::query()
                    ->with(['documentStatus', 'documentType', 'referencedSop.documentStatus'])
                    ->lockForUpdate()
                    ->findOrFail($document->getKey());

                if (! $lockedDocument->canBeIssued()) {
                    throw ValidationException::withMessages([
                        'issuance' => 'The document is no longer eligible for controlled-copy issuance.',
                    ]);
                }

                $issuanceType = $this->issuanceType($lockedDocument, $data);
                $batch = $this->createPaperBatch($lockedDocument, $issuer, $copyCount, $issuanceType);
                $issuances = collect();

                for ($index = 0; $index < $copyCount; $index++) {
                    $issuances->push($this->createCopy($lockedDocument, $issuer, $data, $issuanceType, $batch));
                }

                if ($batch instanceof DocumentIssuanceBatch) {
                    $first = $issuances->first();
                    $last = $issuances->last();

                    $batch->update([
                        'first_issuance_number' => $first instanceof DocumentIssuance ? $first->issuance_number : null,
                        'last_issuance_number' => $last instanceof DocumentIssuance ? $last->issuance_number : null,
                    ]);
                }

                return $issuances;
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

    /**
     * @param  array{
     *     issued_to_user_id?: int|null,
     *     issued_to_department_id?: int|null,
     *     issued_to_location?: string|null,
     *     notes?: string|null,
     *     issuance_type?: string|null,
     *     copy_count?: int|string|null,
     *     batch_number?: string|null,
     *     product_name?: string|null,
     *     log_frequency?: string|null,
     *     log_period_start?: string|null,
     *     log_period_end?: string|null,
     *     supervisor_id?: int|null
     * }  $data
     */
    private function createCopy(
        ControlledDocument $document,
        User $issuer,
        array $data,
        string $issuanceType,
        ?DocumentIssuanceBatch $batch,
    ): DocumentIssuance {
        $copyNumber = $this->documentNumberGeneratorService->nextCopyNumber($document);
        $issuanceNumber = $this->documentNumberGeneratorService->generateIssuanceNumber($document, $copyNumber);
        $watermarkCode = $this->documentNumberGeneratorService->generateWatermarkCode($document, $copyNumber);

        $issuance = DocumentIssuance::query()->create([
            'document_id' => $document->id,
            'issuance_batch_id' => $batch?->id,
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
            document: $document,
        );

        return $issuance;
    }

    /**
     * @param  array{issuance_type?: string|null}  $data
     */
    private function issuanceType(ControlledDocument $document, array $data): string
    {
        $issuanceType = $data['issuance_type'] ?? ($document->documentType?->requiresExecutionRecord()
            ? DocumentIssuance::TYPE_EXECUTION
            : DocumentIssuance::TYPE_REFERENCE);

        if (! in_array($issuanceType, [
            DocumentIssuance::TYPE_REFERENCE,
            DocumentIssuance::TYPE_EXECUTION,
            DocumentIssuance::TYPE_PAPER,
        ], true)) {
            throw ValidationException::withMessages([
                'issuance_type' => 'Select a read-only reference copy, a writable execution record, or a paper copy.',
            ]);
        }

        if ($issuanceType === DocumentIssuance::TYPE_EXECUTION && ! $document->documentType?->requiresExecutionRecord()) {
            throw ValidationException::withMessages([
                'issuance_type' => 'This master document is not configured as a writable GMP record.',
            ]);
        }

        return $issuanceType;
    }

    /**
     * @param  array{copy_count?: int|string|null, issuance_type?: string|null}  $data
     */
    private function copyCount(array $data): int
    {
        if (! array_key_exists('copy_count', $data) || $data['copy_count'] === null || $data['copy_count'] === '') {
            return 1;
        }

        $rules = ['required', 'integer', 'min:1'];
        $message = 'Enter a whole number of copies of at least 1.';

        if (($data['issuance_type'] ?? null) === DocumentIssuance::TYPE_PAPER) {
            $rules[] = 'max:'.DocumentIssuanceBatch::MAX_PAPER_COPIES;
            $message = 'Enter a whole number of paper copies between 1 and '.DocumentIssuanceBatch::MAX_PAPER_COPIES.'.';
        }

        $validator = Validator::make(
            ['copy_count' => $data['copy_count']],
            ['copy_count' => $rules],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'copy_count' => $message,
            ]);
        }

        return (int) $data['copy_count'];
    }

    private function createPaperBatch(
        ControlledDocument $document,
        User $issuer,
        int $copyCount,
        string $issuanceType,
    ): ?DocumentIssuanceBatch {
        if ($issuanceType !== DocumentIssuance::TYPE_PAPER) {
            return null;
        }

        return DocumentIssuanceBatch::query()->create([
            'document_id' => $document->id,
            'issued_by' => $issuer->id,
            'copy_count' => $copyCount,
            'pack_status' => DocumentIssuanceBatch::PACK_PENDING,
        ]);
    }
}
