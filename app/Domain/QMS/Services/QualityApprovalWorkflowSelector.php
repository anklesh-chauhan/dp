<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;

final class QualityApprovalWorkflowSelector implements ApprovalWorkflowDefinitionSelector
{
    public function selectFor(ApprovableSubject $subject): ?ApprovalWorkflowDefinition
    {
        $query = QualityApprovalWorkflow::query()
            ->where('subject_type', $subject::class)
            ->where('is_active', true)
            ->with(['steps.department'])
            ->orderBy('id');

        $departmentWorkflow = (clone $query)
            ->where('department_id', $subject->approvalSubjectDepartmentId())
            ->first();

        if ($departmentWorkflow !== null) {
            return $departmentWorkflow;
        }

        return $query->whereNull('department_id')->first();
    }
}
