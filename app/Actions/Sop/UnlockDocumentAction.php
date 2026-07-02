<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopDocument;
use App\Models\User;
use App\Services\Sop\DocumentLockService;

class UnlockDocumentAction
{
    public function __construct(private readonly DocumentLockService $documentLockService) {}

    public function execute(SopDocument $document, User $user, bool $force = false): SopDocument
    {
        return $this->documentLockService->unlockDocument($document, $user, $force);
    }
}
