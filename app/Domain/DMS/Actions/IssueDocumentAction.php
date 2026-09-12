<?php

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\User;
use Illuminate\Support\Collection;

class IssueDocumentAction
{
    public function __construct(private readonly DocumentIssuanceService $documentIssuanceService) {}

    /**
     * @param  array{
     *     issued_to_user_id?: int|null,
     *     issued_to_department_id?: int|null,
     *     issued_to_location?: string|null,
     *     notes?: string|null,
     *     issuance_type?: string|null,
     *     copy_count?: int|string|null
     * }  $data
     * @return Collection<int, DocumentIssuance>
     */
    public function execute(ControlledDocument $document, User $issuer, array $data = []): Collection
    {
        return $this->documentIssuanceService->issueCopies($document, $issuer, $data);
    }
}
