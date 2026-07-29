<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SopReferenceService
{
    /**
     * @return array<int, string>
     */
    public function effectiveSopOptions(?int $templateId): array
    {
        if ($templateId === null || $templateId === 0) {
            return [];
        }

        $departmentId = DocumentTemplate::query()->whereKey($templateId)->value('department_id');

        if ($departmentId === null) {
            return [];
        }

        return ControlledDocument::query()
            ->where('department_id', $departmentId)
            ->whereHas('documentStatus', fn (Builder $query): Builder => $query->where('code', DocumentStatus::EFFECTIVE))
            ->whereHas('documentType', fn (Builder $query): Builder => $query->where('code', DocumentType::SOP))
            ->orderBy('document_number')
            ->pluck('document_number', 'id')
            ->all();
    }

    /**
     * @return array{
     *     referenced_controlled_document_id: int,
     *     referenced_sop_number: string,
     *     referenced_sop_version: int,
     *     referenced_sop_effective_date: string|null
     * }
     */
    public function resolve(int $referencedControlledDocumentId, Department $department): array
    {
        $sop = ControlledDocument::query()
            ->whereKey($referencedControlledDocumentId)
            ->where('department_id', $department->id)
            ->whereHas('documentStatus', fn ($query) => $query->where('code', DocumentStatus::EFFECTIVE))
            ->first();

        if ($sop === null) {
            throw ValidationException::withMessages([
                'referenced_controlled_document_id' => 'An effective SOP from the same department must be selected.',
            ]);
        }

        return [
            'referenced_controlled_document_id' => $sop->id,
            'referenced_sop_number' => $sop->document_number,
            'referenced_sop_version' => $sop->version,
            'referenced_sop_effective_date' => $sop->effective_date?->toDateString(),
        ];
    }
}
