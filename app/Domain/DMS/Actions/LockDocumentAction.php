<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentLockService;
use App\Models\ControlledDocument;
use App\Models\User;

class LockDocumentAction
{
    public function __construct(private readonly DocumentLockService $documentLockService) {}

    public function execute(ControlledDocument $document, User $user): ControlledDocument
    {
        return $this->documentLockService->lockDocument($document, $user);
    }
}
