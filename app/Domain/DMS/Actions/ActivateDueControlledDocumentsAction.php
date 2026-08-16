<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentEffectivenessService;

class ActivateDueControlledDocumentsAction
{
    public function __construct(private readonly DocumentEffectivenessService $documentEffectivenessService) {}

    public function execute(): int
    {
        return $this->documentEffectivenessService->activateDueDocuments();
    }
}
