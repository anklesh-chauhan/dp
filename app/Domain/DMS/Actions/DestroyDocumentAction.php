<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\RetentionLifecycleService;
use App\Models\ControlledDocument;
use App\Models\User;

class DestroyDocumentAction
{
    public function __construct(private readonly RetentionLifecycleService $retentionLifecycleService) {}

    public function execute(ControlledDocument $document, User $user, string $reason): ControlledDocument
    {
        return $this->retentionLifecycleService->destroyDocument($document, $user, $reason);
    }
}
