<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Data\ControlledDocumentData;
use App\Domain\DMS\Services\SopGeneratorService;
use App\Models\ControlledDocument;

class CreateDocumentFromTemplateAction
{
    public function __construct(private readonly SopGeneratorService $generatorService) {}

    public function execute(ControlledDocumentData $data): ControlledDocument
    {
        return $this->generatorService->generate($data);
    }
}
