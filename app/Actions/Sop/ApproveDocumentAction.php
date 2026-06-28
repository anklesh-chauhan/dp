<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopApproval;
use App\Models\User;
use App\Services\Sop\WorkflowEngineService;

class ApproveDocumentAction
{
    public function __construct(private readonly WorkflowEngineService $workflowEngineService) {}

    public function execute(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        return $this->workflowEngineService->approve($approval, $approver, $comments);
    }
}
