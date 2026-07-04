<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Models\Department;
use App\Models\DocumentStatus;
use App\Models\SopDocument;
use Illuminate\Validation\ValidationException;

class SopReferenceService
{
    /**
     * @return array{
     *     referenced_sop_document_id: int,
     *     referenced_sop_number: string,
     *     referenced_sop_version: int,
     *     referenced_sop_effective_date: string|null
     * }
     */
    public function resolve(int $referencedSopDocumentId, Department $department): array
    {
        $sop = SopDocument::query()
            ->whereKey($referencedSopDocumentId)
            ->where('department_id', $department->id)
            ->whereHas('documentStatus', fn ($query) => $query->where('code', DocumentStatus::EFFECTIVE))
            ->first();

        if ($sop === null) {
            throw ValidationException::withMessages([
                'referenced_sop_document_id' => 'An effective SOP from the same department must be selected.',
            ]);
        }

        return [
            'referenced_sop_document_id' => $sop->id,
            'referenced_sop_number' => $sop->document_number,
            'referenced_sop_version' => $sop->version,
            'referenced_sop_effective_date' => $sop->effective_date?->toDateString(),
        ];
    }
}
