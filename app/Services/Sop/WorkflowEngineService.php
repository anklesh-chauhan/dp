<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Enums\ApprovalDecision;
use App\Enums\DocumentStatus;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopWorkflow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkflowEngineService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function start(SopDocument $document, ?SopWorkflow $workflow = null): void
    {
        $workflow ??= SopWorkflow::query()
            ->where('is_active', true)
            ->with('steps')
            ->first();

        if ($workflow === null) {
            return;
        }

        DB::transaction(function () use ($document, $workflow): void {
            foreach ($workflow->steps as $step) {
                SopApproval::query()->firstOrCreate([
                    'document_id' => $document->id,
                    'workflow_step_id' => $step->id,
                ], [
                    'decision' => ApprovalDecision::Pending,
                ]);
            }

            $document->update(['status' => DocumentStatus::UnderReview]);
            $this->auditLogService->log($document, SopAuditLog::ACTION_SUBMITTED, null, ['workflow_id' => $workflow->id]);
        });
    }

    public function approve(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
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

            $this->auditLogService->log($document, SopAuditLog::ACTION_APPROVED, null, [
                'approval_id' => $approval->id,
                'approved_by' => $approver->id,
            ], $approver->id);

            return $approval;
        });
    }

    public function reject(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        return $this->decide($approval, $approver, ApprovalDecision::Rejected, DocumentStatus::Rejected, SopAuditLog::ACTION_REJECTED, $comments);
    }

    public function return(SopApproval $approval, User $approver, ?string $comments = null): SopApproval
    {
        return $this->decide($approval, $approver, ApprovalDecision::Returned, DocumentStatus::Draft, SopAuditLog::ACTION_RETURNED, $comments);
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
            $document->update(['status' => $documentStatus]);

            $this->auditLogService->log($document, $auditAction, null, [
                'approval_id' => $approval->id,
                'decision' => $decision->value,
            ], $approver->id);

            return $approval;
        });
    }
}
