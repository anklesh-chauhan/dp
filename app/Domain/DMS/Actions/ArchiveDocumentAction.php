<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\RetentionLifecycleService;
use App\Models\ControlledDocument;
use App\Models\User;

class ArchiveDocumentAction
{
    public function __construct(private readonly RetentionLifecycleService $retentionLifecycleService) {}

    public function execute(ControlledDocument $document, User $user, ?string $reason = null): ControlledDocument
    {
        return $this->retentionLifecycleService->archiveDocument($document, $user, $reason);
    }
}
