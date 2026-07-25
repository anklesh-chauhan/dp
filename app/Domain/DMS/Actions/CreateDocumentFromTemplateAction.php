<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Data\SopDocumentData;
use App\Domain\DMS\Services\SopGeneratorService;
use App\Models\SopDocument;

class CreateDocumentFromTemplateAction
{
    public function __construct(private readonly SopGeneratorService $generatorService) {}

    public function execute(SopDocumentData $data): SopDocument
    {
        return $this->generatorService->generate($data);
    }
}
