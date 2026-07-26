<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class QualityApprovalInstancePersistence implements ApprovalInstancePersistence
{
    public function initializeFor(
        ApprovableSubject $subject,
        ApprovalWorkflowDefinition $workflow,
    ): void {
        if (! $subject instanceof Model) {
            throw new InvalidArgumentException('Quality approval subjects must be persisted Eloquent models.');
        }

        if (! $workflow instanceof QualityApprovalWorkflow) {
            throw new InvalidArgumentException('Quality approval persistence requires a quality approval workflow.');
        }

        $steps = collect($workflow->approvalWorkflowStepDefinitions());
        $subjectInstances = QualityApprovalInstance::query()
            ->whereMorphedTo('subject', $subject)
            ->where('workflow_id', $workflow->getKey());
        $latestSubmissionUuid = (clone $subjectInstances)
            ->latest('id')
            ->value('submission_uuid');

        if (
            is_string($latestSubmissionUuid)
            && (clone $subjectInstances)->where('submission_uuid', $latestSubmissionUuid)->count() === $steps->count()
            && ! (clone $subjectInstances)
                ->where('submission_uuid', $latestSubmissionUuid)
                ->where('decision_code', '!=', 'pending')
                ->exists()
        ) {
            return;
        }

        $submissionUuid = (string) Str::uuid();

        foreach ($steps as $step) {
            if (! $step instanceof QualityApprovalWorkflowStep) {
                throw new InvalidArgumentException('Quality workflows require quality workflow steps.');
            }

            QualityApprovalInstance::query()->firstOrCreate([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'submission_uuid' => $submissionUuid,
                'workflow_step_id' => $step->getKey(),
            ], [
                'instance_uuid' => (string) Str::uuid(),
                'workflow_id' => $workflow->getKey(),
                'decision_code' => 'pending',
            ]);
        }
    }
}
