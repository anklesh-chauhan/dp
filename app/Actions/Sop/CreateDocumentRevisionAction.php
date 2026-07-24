<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopDocument;
use App\Models\User;
use App\Services\Sop\DocumentRevisionService;

class CreateDocumentRevisionAction
{
    public function __construct(private readonly DocumentRevisionService $documentRevisionService) {}

    public function execute(SopDocument $document, User $user, string $reason): SopDocument
    {
        return $this->documentRevisionService->create($document, $user, $reason);
    }
}
