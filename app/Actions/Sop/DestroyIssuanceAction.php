<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\DocumentIssuance;
use App\Models\User;
use App\Services\Sop\DocumentIssuanceService;

class DestroyIssuanceAction
{
    public function __construct(private readonly DocumentIssuanceService $documentIssuanceService) {}

    public function execute(DocumentIssuance $issuance, User $user, string $reason): DocumentIssuance
    {
        return $this->documentIssuanceService->destroyCopy($issuance, $user, $reason);
    }
}
