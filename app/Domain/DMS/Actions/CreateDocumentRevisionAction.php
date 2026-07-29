<?php

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentRevisionService;
use App\Models\ControlledDocument;
use App\Models\User;

class CreateDocumentRevisionAction
{
    public function __construct(private readonly DocumentRevisionService $documentRevisionService) {}

    public function execute(ControlledDocument $document, User $user, string $reason): ControlledDocument
    {
        return $this->documentRevisionService->create($document, $user, $reason);
    }
}
