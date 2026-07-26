<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\QualityApprovalInstanceFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

final class QualityApprovalInstance extends Model implements ApprovalInstance
{
    /** @use HasFactory<QualityApprovalInstanceFactory> */
    use HasFactory;

    protected $fillable = [
        'instance_uuid',
        'submission_uuid',
        'subject_type',
        'subject_id',
        'workflow_id',
        'workflow_step_id',
        'decision_code',
        'decided_by',
        'comments',
        'decided_at',
        'signature_hash',
        'signature_ip_address',
        'signature_user_agent',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['decided_at' => 'immutable_datetime'];
    }

    public function approvalInstanceKey(): int|string|null
    {
        return $this->instance_uuid;
    }

    public function approvalInstanceSubject(): ApprovableSubject
    {
        $subject = $this->subject;

        if (! $subject instanceof ApprovableSubject) {
            throw new LogicException('Quality approval subject must implement ApprovableSubject.');
        }

        return $subject;
    }

    public function approvalInstanceWorkflowStepDefinition(): ApprovalWorkflowStepDefinition
    {
        return $this->workflowStep;
    }

    public function approvalInstanceDecisionCode(): ?string
    {
        return $this->decision_code;
    }

    public function approvalInstanceApproverId(): ?int
    {
        return $this->decided_by === null ? null : (int) $this->decided_by;
    }

    public function approvalInstanceComments(): ?string
    {
        return $this->comments;
    }

    public function approvalInstanceDecidedAt(): ?DateTimeInterface
    {
        return $this->decided_at;
    }

    public function approvalInstanceSignatureHash(): ?string
    {
        return $this->signature_hash;
    }

    public function signatureRecordKey(): int|string|null
    {
        return $this->approvalInstanceKey();
    }

    public function signatureMeaning(): ?string
    {
        return $this->signature_hash === null ? null : $this->decision_code;
    }

    public function signatureSignerId(): int|string|null
    {
        return $this->signature_hash === null ? null : $this->decided_by;
    }

    public function signatureTimestamp(): ?DateTimeInterface
    {
        return $this->signature_hash === null ? null : $this->decided_at;
    }

    public function signatureHash(): ?string
    {
        return $this->signature_hash;
    }

    public function signatureReason(): ?string
    {
        return $this->signature_hash === null ? null : $this->comments;
    }

    public function signatureIpAddress(): ?string
    {
        return $this->signature_ip_address;
    }

    public function signatureUserAgent(): ?string
    {
        return $this->signature_user_agent;
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<QualityApprovalWorkflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(QualityApprovalWorkflow::class, 'workflow_id');
    }

    /** @return BelongsTo<QualityApprovalWorkflowStep, $this> */
    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(QualityApprovalWorkflowStep::class, 'workflow_step_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
