<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentEffectivenessService;
use App\Models\ControlledDocument;
use App\Models\User;
use Carbon\CarbonInterface;

class MakeDocumentEffectiveAction
{
    public function __construct(private readonly DocumentEffectivenessService $documentEffectivenessService) {}

    public function execute(
        ControlledDocument $document,
        User $user,
        CarbonInterface|string $effectiveDate,
        ?string $reason = null,
    ): ControlledDocument {
        return $this->documentEffectivenessService->release($document, $user, $effectiveDate, $reason);
    }
}
