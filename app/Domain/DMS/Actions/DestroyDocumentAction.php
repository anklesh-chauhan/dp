<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\RetentionLifecycleService;
use App\Models\SopDocument;
use App\Models\User;

class DestroyDocumentAction
{
    public function __construct(private readonly RetentionLifecycleService $retentionLifecycleService) {}

    public function execute(SopDocument $document, User $user, string $reason): SopDocument
    {
        return $this->retentionLifecycleService->destroyDocument($document, $user, $reason);
    }
}
