<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Models\SopWorkflow;

class SopWorkflowDefinitionSelector implements ApprovalWorkflowDefinitionSelector
{
    public function selectFor(ApprovableSubject $subject): ?ApprovalWorkflowDefinition
    {
        $departmentWorkflow = SopWorkflow::query()
            ->where('is_active', true)
            ->where('department_id', $subject->approvalSubjectDepartmentId())
            ->with(['steps.department'])
            ->first();

        if ($departmentWorkflow !== null) {
            return $departmentWorkflow;
        }

        return SopWorkflow::query()
            ->where('is_active', true)
            ->where('department_id', null)
            ->with(['steps.department'])
            ->first();
    }
}
