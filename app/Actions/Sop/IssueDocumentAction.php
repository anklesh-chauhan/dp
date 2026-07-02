<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\DocumentIssuance;
use App\Models\SopDocument;
use App\Models\User;
use App\Services\Sop\DocumentIssuanceService;

class IssueDocumentAction
{
    public function __construct(private readonly DocumentIssuanceService $documentIssuanceService) {}

    /**
     * @param  array{
     *     issued_to_user_id?: int|null,
     *     issued_to_department_id?: int|null,
     *     issued_to_location?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function execute(SopDocument $document, User $issuer, array $data = []): DocumentIssuance
    {
        return $this->documentIssuanceService->issue($document, $issuer, $data);
    }
}
