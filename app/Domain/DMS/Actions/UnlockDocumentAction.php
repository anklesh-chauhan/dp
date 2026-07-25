<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentLockService;
use App\Models\SopDocument;
use App\Models\User;

class UnlockDocumentAction
{
    public function __construct(private readonly DocumentLockService $documentLockService) {}

    public function execute(SopDocument $document, User $user, bool $force = false): SopDocument
    {
        return $this->documentLockService->unlockDocument($document, $user, $force);
    }
}
