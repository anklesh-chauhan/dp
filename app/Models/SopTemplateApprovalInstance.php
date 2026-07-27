<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use Database\Factories\SopTemplateApprovalInstanceFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopTemplateApprovalInstance extends Model implements ApprovalInstance
{
    /** @use HasFactory<SopTemplateApprovalInstanceFactory> */
    use HasFactory;

    protected $fillable = [
        'instance_uuid',
        'submission_uuid',
        'sop_template_version_id',
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
        return $this->templateVersion;
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
        return $this->decided_by;
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
        return $this->instance_uuid;
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

    /** @return BelongsTo<SopTemplateVersion, $this> */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(SopTemplateVersion::class, 'sop_template_version_id');
    }

    /** @return BelongsTo<SopWorkflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(SopWorkflow::class, 'workflow_id');
    }

    /** @return BelongsTo<SopWorkflowStep, $this> */
    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(SopWorkflowStep::class, 'workflow_step_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
