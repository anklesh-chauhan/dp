<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Models\ApprovalDecision;
use App\Models\SopApproval;
use DateTimeInterface;
use InvalidArgumentException;

class SopApprovalDecisionPersistenceAdapter implements ApprovalDecisionPersistence
{
    public function recordDecision(
        ApprovalInstance $approval,
        string $decisionCode,
        int $decidedById,
        ?string $comments,
        DateTimeInterface $decidedAt,
        ?string $signatureHash = null,
        ?string $signatureIpAddress = null,
        ?string $signatureUserAgent = null,
    ): ApprovalInstance {
        if (! $approval instanceof SopApproval) {
            throw new InvalidArgumentException(
                'The SOP approval decision adapter requires a SopApproval instance.'
            );
        }

        $values = [
            'approved_by' => $decidedById,
            'approval_decision_id' => ApprovalDecision::idFor($decisionCode),
            'comments' => $comments,
            'approved_at' => $decidedAt,
            'signature_ip_address' => $signatureIpAddress,
            'signature_user_agent' => $signatureUserAgent,
        ];

        if ($signatureHash !== null) {
            $values['signature_hash'] = $signatureHash;
        }

        $approval->update($values);

        return $approval;
    }
}
