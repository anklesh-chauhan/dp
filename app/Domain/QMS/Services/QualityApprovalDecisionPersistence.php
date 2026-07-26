<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Exceptions\WorkflowException;
use DateTimeInterface;
use InvalidArgumentException;

final class QualityApprovalDecisionPersistence implements ApprovalDecisionPersistence
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
        if (! $approval instanceof QualityApprovalInstance) {
            throw new InvalidArgumentException('Quality decision persistence requires a quality approval instance.');
        }

        if (! in_array($decisionCode, [
            ApprovalDecisionCode::APPROVED->value,
            ApprovalDecisionCode::REJECTED->value,
            ApprovalDecisionCode::RETURNED->value,
        ], true)) {
            throw new InvalidArgumentException("Unsupported quality approval decision '{$decisionCode}'.");
        }

        $record = QualityApprovalInstance::query()
            ->where('instance_uuid', $approval->instance_uuid)
            ->lockForUpdate()
            ->firstOrFail();

        if ($record->decision_code !== 'pending') {
            throw new WorkflowException(message: 'This quality approval has already been decided.');
        }

        $record->update([
            'decision_code' => $decisionCode,
            'decided_by' => $decidedById,
            'comments' => filled($comments) ? trim((string) $comments) : null,
            'decided_at' => $decidedAt,
            'signature_hash' => $signatureHash,
            'signature_ip_address' => $signatureIpAddress,
            'signature_user_agent' => $signatureUserAgent,
        ]);

        return $record->refresh();
    }
}
