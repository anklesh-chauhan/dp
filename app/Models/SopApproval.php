<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use Database\Factories\SopApprovalFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopApproval extends Model implements ApprovalInstance
{
    /** @use HasFactory<SopApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'workflow_step_id',
        'approved_by',
        'approval_decision_id',
        'comments',
        'approved_at',
        'signature_hash',
        'signature_ip_address',
        'signature_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function approvalInstanceKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function approvalInstanceSubject(): ApprovableSubject
    {
        return $this->document;
    }

    public function approvalInstanceWorkflowStepDefinition(): ApprovalWorkflowStepDefinition
    {
        return $this->workflowStep;
    }

    public function approvalInstanceDecisionCode(): ?string
    {
        return $this->approvalDecision?->code;
    }

    public function approvalInstanceApproverId(): ?int
    {
        return $this->approved_by === null ? null : (int) $this->approved_by;
    }

    public function approvalInstanceComments(): ?string
    {
        return $this->comments;
    }

    public function approvalInstanceDecidedAt(): ?DateTimeInterface
    {
        return $this->approved_at;
    }

    public function approvalInstanceSignatureHash(): ?string
    {
        return $this->signature_hash;
    }

    public function signatureMeaning(): ?string
    {
        return $this->approvalInstanceDecisionCode();
    }

    public function signatureRecordKey(): int|string|null
    {
        return $this->approvalInstanceKey();
    }

    public function signatureSignerId(): ?int
    {
        return $this->approvalInstanceApproverId();
    }

    public function signatureTimestamp(): ?DateTimeInterface
    {
        return $this->approvalInstanceDecidedAt();
    }

    public function signatureHash(): ?string
    {
        return $this->approvalInstanceSignatureHash();
    }

    public function signatureReason(): ?string
    {
        return $this->approvalInstanceComments();
    }

    public function signatureIpAddress(): ?string
    {
        return $this->signature_ip_address;
    }

    public function signatureUserAgent(): ?string
    {
        return $this->signature_user_agent;
    }

    /**
     * @return BelongsTo<SopDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'document_id');
    }

    /**
     * @return BelongsTo<SopWorkflowStep, $this>
     */
    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(SopWorkflowStep::class, 'workflow_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<ApprovalDecision, $this>
     */
    public function approvalDecision(): BelongsTo
    {
        return $this->belongsTo(ApprovalDecision::class);
    }

    /**
     * @param  Builder<SopApproval>  $query
     * @return Builder<SopApproval>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereHas('approvalDecision', fn (Builder $decisionQuery): Builder => $decisionQuery->where('code', ApprovalDecision::PENDING));
    }

    /**
     * @param  Builder<SopApproval>  $query
     * @return Builder<SopApproval>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->department_id === null || $user->hasRole(SopRole::ADMINISTRATOR)) {
            return $query;
        }

        return $query->where(function (Builder $approvalQuery) use ($user): void {
            $approvalQuery
                ->whereHas('workflowStep', fn (Builder $stepQuery): Builder => $stepQuery->where('department_id', $user->department_id))
                ->orWhere(function (Builder $nestedQuery) use ($user): void {
                    $nestedQuery
                        ->whereHas('workflowStep', fn (Builder $stepQuery): Builder => $stepQuery->whereNull('department_id'))
                        ->whereHas('document', fn (Builder $documentQuery): Builder => $documentQuery->where('department_id', $user->department_id));
                });
        });
    }

    /**
     * @param  Builder<SopApproval>  $query
     * @return Builder<SopApproval>
     */
    public function scopeActionableFor(Builder $query, User $user): Builder
    {
        return $query->pending()->visibleToUser($user);
    }

    public function isActionable(): bool
    {
        if (! $this->approvalDecision?->hasCode(ApprovalDecision::PENDING)) {
            return false;
        }

        $this->loadMissing(['document.approvals.workflowStep', 'workflowStep']);

        $previousMandatoryStepsPending = $this->document->approvals
            ->filter(fn (SopApproval $approval): bool => $approval->workflowStep->step_no < $this->workflowStep->step_no
                && $approval->workflowStep->is_mandatory)
            ->contains(fn (SopApproval $approval): bool => ! $approval->approvalDecision?->hasCode(ApprovalDecision::APPROVED));

        return ! $previousMandatoryStepsPending;
    }

    public function canBeApprovedBy(User $user): bool
    {
        app(ApprovalDecisionAuthorization::class)->authorizeDecision($this, $user);

        return true;
    }
}
