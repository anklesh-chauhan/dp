<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopDocument;
use App\Models\User;
use App\Services\Sop\RetentionLifecycleService;

class ArchiveDocumentAction
{
    public function __construct(private readonly RetentionLifecycleService $retentionLifecycleService) {}

    public function execute(SopDocument $document, User $user, ?string $reason = null): SopDocument
    {
        return $this->retentionLifecycleService->archiveDocument($document, $user, $reason);
    }
}
