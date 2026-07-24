<?php

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentRevisionService;
use App\Models\SopDocument;
use App\Models\User;

class CreateDocumentRevisionAction
{
    public function __construct(private readonly DocumentRevisionService $documentRevisionService) {}

    public function execute(SopDocument $document, User $user, string $reason): SopDocument
    {
        return $this->documentRevisionService->create($document, $user, $reason);
    }
}
