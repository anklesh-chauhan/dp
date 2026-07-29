<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\ControlledDocument;
use App\Models\User;
use App\Services\Sop\WorkflowEngineService;

class SubmitDocumentAction
{
    public function __construct(private readonly WorkflowEngineService $workflowEngineService) {}

    public function execute(ControlledDocument $document, User $submitter): ControlledDocument
    {
        $this->workflowEngineService->start($document, $submitter);

        return $document->refresh()->load(['approvals.workflowStep']);
    }
}
