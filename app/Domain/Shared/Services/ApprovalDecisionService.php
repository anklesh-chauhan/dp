<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\WorkflowDecisionNotifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalDecisionService
{
    public function __construct(
        private readonly ApprovalDecisionAuthorization $approvalDecisionAuthorization,
        private readonly ApprovalDecisionOutcome $approvalDecisionOutcome,
        private readonly ApprovalDecisionPersistence $approvalDecisionPersistence,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
        private readonly Request $request,
        private readonly WorkflowDecisionNotifier $workflowNotifications,
    ) {}

    public function approve(
        ApprovalInstance $approval,
        User $approver,
        ?string $comments = null,
    ): ApprovalInstance {
        return $this->decide($approval, $approver, ApprovalDecisionCode::APPROVED, $comments);
    }

    public function reject(
        ApprovalInstance $approval,
        User $approver,
        ?string $comments = null,
    ): ApprovalInstance {
        return $this->decide($approval, $approver, ApprovalDecisionCode::REJECTED, $comments);
    }

    public function return(
        ApprovalInstance $approval,
        User $approver,
        ?string $comments = null,
    ): ApprovalInstance {
        return $this->decide($approval, $approver, ApprovalDecisionCode::RETURNED, $comments);
    }

    private function decide(
        ApprovalInstance $approval,
        User $approver,
        ApprovalDecisionCode $decisionCode,
        ?string $comments,
    ): ApprovalInstance {
        $this->approvalDecisionAuthorization->authorizeDecision($approval, $approver);

        $decidedAt = now();
        $signatureIpAddress = $this->request->ip();
        $signatureUserAgent = $this->request->userAgent();
        $signatureHash = $this->electronicSignatureHasher->hashFor(
            recordKey: $approval->approvalInstanceKey(),
            meaning: $decisionCode->value,
            signerId: $approver->id,
            signedAt: $decidedAt,
            reason: $comments,
            ipAddress: $signatureIpAddress,
            userAgent: $signatureUserAgent,
        );

        return DB::transaction(function () use ($approval, $approver, $decisionCode, $comments, $decidedAt, $signatureHash, $signatureIpAddress, $signatureUserAgent): ApprovalInstance {
            $approval = $this->approvalDecisionPersistence->recordDecision(
                approval: $approval,
                decisionCode: $decisionCode->value,
                decidedById: $approver->id,
                comments: $comments,
                decidedAt: $decidedAt,
                signatureHash: $signatureHash,
                signatureIpAddress: $signatureIpAddress,
                signatureUserAgent: $signatureUserAgent,
            );

            $approval = $this->approvalDecisionOutcome->applyOutcome(
                approval: $approval,
                decisionCode: $decisionCode->value,
                decidedBy: $approver,
            );

            $this->workflowNotifications->notifyDecision($approval, $approver, $decisionCode);

            return $approval;
        });
    }
}
