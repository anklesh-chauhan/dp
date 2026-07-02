<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Enums\ApprovalDecision;
use App\Enums\DocumentStatus;
use App\Enums\SopRole;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopWorkflow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowEngineService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly DocumentLockService $documentLockService,
    ) {}

    public function start(SopDocument $document, User $submitter, ?SopWorkflow $workflow = null): void
    {
        if ($document->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft documents can be submitted for approval.',
            ]);
        }

        if (! $this->canSubmit($document, $submitter)) {
            throw ValidationException::withMessages([
                'submit' => 'You are not authorized to submit this document for approval.',
            ]);
        }

        $workflow ??= $this->resolveWorkflow($document);

        if ($workflow === null) {
            throw ValidationException::withMessages([
                'workflow' => 'No active approval workflow is configured for this department.',
            ]);
        }

        DB::transaction(function () use ($document, $workflow, $submitter): void {
            if ($document->isLocked()) {
                $this->documentLockService->unlockDocument($document, $submitter, force: true);
            }

            $document->approvals()->update([
                'decision' => ApprovalDecision::Pending,
                'approved_by' => null,
                'comments' => null,
                'approved_at' => null,
                'signature_hash' => null,
            ]);

            foreach ($workflow->steps as $step) {
                SopApproval::query()->firstOrCreate([
                    'document_id' => $document->id,
                    'workflow_step_id' => $step->id,
                ], [
                    'decision' => ApprovalDecision::Pending,
                ]);
            }

            $document->update(['status' => DocumentStatus::UnderReview]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_SUBMITTED,
                newValues: [
                    'workflow_id' => $workflow->id,
                    'submitted_by' => $submitter->id,
                ],
                userId: $submitter->id,
                document: $document,
            );
        });
    }

    public function approve(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        if (! $approval->canBeApprovedBy($approver)) {
            throw ValidationException::withMessages([
                'approval' => 'This approval step is not available for you yet.',
            ]);
        }

        return DB::transaction(function () use ($approval, $approver, $comments): SopApproval {
            $approval->update([
                'approved_by' => $approver->id,
                'decision' => ApprovalDecision::Approved,
                'comments' => $comments,
                'approved_at' => now(),
                'signature_hash' => hash('sha256', $approval->id.'|'.$approver->id.'|'.now()->toISOString()),
            ]);

            $document = $approval->document()->with('approvals.workflowStep')->firstOrFail();
            $mandatoryApprovals = $document->approvals->filter(fn (SopApproval $item): bool => $item->workflowStep->is_mandatory);

            if ($mandatoryApprovals->every(fn (SopApproval $item): bool => $item->decision === ApprovalDecision::Approved)) {
                $document->update(['status' => DocumentStatus::Effective]);
            } else {
                $document->update(['status' => DocumentStatus::Approved]);
            }

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_APPROVED,
                newValues: [
                    'approval_id' => $approval->id,
                    'approved_by' => $approver->id,
                    'step_type' => $approval->workflowStep->approval_type?->value,
                ],
                userId: $approver->id,
                document: $document,
            );

            return $approval;
        });
    }

    public function reject(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        if (! $approval->canBeApprovedBy($approver)) {
            throw ValidationException::withMessages([
                'approval' => 'This approval step is not available for you yet.',
            ]);
        }

        return $this->decide($approval, $approver, ApprovalDecision::Rejected, DocumentStatus::Rejected, SopAuditLog::ACTION_REJECTED, $comments);
    }

    public function return(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        if (! $approval->canBeApprovedBy($approver)) {
            throw ValidationException::withMessages([
                'approval' => 'This approval step is not available for you yet.',
            ]);
        }

        return DB::transaction(function () use ($approval, $approver, $comments): SopApproval {
            $approval->update([
                'approved_by' => $approver->id,
                'decision' => ApprovalDecision::Returned,
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            $document = $approval->document;
            $document->update([
                'status' => DocumentStatus::Draft,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_RETURNED,
                newValues: [
                    'approval_id' => $approval->id,
                    'returned_by' => $approver->id,
                ],
                userId: $approver->id,
                document: $document,
            );

            return $approval;
        });
    }

    public function resolveWorkflow(SopDocument $document): ?SopWorkflow
    {
        $departmentWorkflow = SopWorkflow::query()
            ->where('is_active', true)
            ->where('department_id', $document->department_id)
            ->with('steps')
            ->first();

        if ($departmentWorkflow !== null) {
            return $departmentWorkflow;
        }

        return SopWorkflow::query()
            ->where('is_active', true)
            ->whereNull('department_id')
            ->with('steps')
            ->first();
    }

    public function canSubmit(SopDocument $document, User $user): bool
    {
        if (! $user->can('Submit:SopDocument') && ! $user->can('Update:SopDocument')) {
            return false;
        }

        if ($user->hasRole(SopRole::Administrator->value)) {
            return true;
        }

        if ($user->hasRole(SopRole::Maker->value)) {
            if ($user->department_id !== null && $user->department_id !== $document->department_id) {
                return false;
            }

            return true;
        }

        return $document->created_by === $user->id || $document->owner_id === $user->id;
    }

    private function decide(SopApproval $approval, User $approver, ApprovalDecision $decision, DocumentStatus $documentStatus, string $auditAction, ?string $comments): SopApproval
    {
        return DB::transaction(function () use ($approval, $approver, $decision, $documentStatus, $auditAction, $comments): SopApproval {
            $approval->update([
                'approved_by' => $approver->id,
                'decision' => $decision,
                'comments' => $comments,
                'approved_at' => now(),
            ]);

            $document = $approval->document;
            $document->update([
                'status' => $documentStatus,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            $this->auditLogService->log(
                action: $auditAction,
                newValues: [
                    'approval_id' => $approval->id,
                    'decision' => $decision->value,
                ],
                userId: $approver->id,
                document: $document,
            );

            return $approval;
        });
    }
}
