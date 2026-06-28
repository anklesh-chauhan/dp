<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Data\SopDocumentData;
use App\Models\SopDocument;
use App\Services\Sop\SopGeneratorService;

class CreateDocumentFromTemplateAction
{
    public function __construct(private readonly SopGeneratorService $generatorService) {}

    public function execute(SopDocumentData $data): SopDocument
    {
        return $this->generatorService->generate($data);
    }
}
