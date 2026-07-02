<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopDocument;
use App\Models\User;
use App\Services\Sop\WorkflowEngineService;

class SubmitDocumentAction
{
    public function __construct(private readonly WorkflowEngineService $workflowEngineService) {}

    public function execute(SopDocument $document, User $submitter): SopDocument
    {
        $this->workflowEngineService->start($document, $submitter);

        return $document->refresh()->load(['approvals.workflowStep']);
    }
}
