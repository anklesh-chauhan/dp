<?php

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Models\DocumentIssuance;
use App\Models\User;

class DestroyIssuanceAction
{
    public function __construct(private readonly DocumentIssuanceService $documentIssuanceService) {}

    public function execute(DocumentIssuance $issuance, User $user, string $reason): DocumentIssuance
    {
        return $this->documentIssuanceService->destroyCopy($issuance, $user, $reason);
    }
}
