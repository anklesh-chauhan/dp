<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\WorkflowException;
use Database\Factories\SopApprovalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopApproval extends Model
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
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
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
        if (! $this->isActionable()) {
            throw new WorkflowException(
                message: 'This approval step is not currently available.'
            );
        }

        $this->loadMissing([
            'workflowStep.role',
            'workflowStep.department',
            'document',
        ]);

        if (! $user->can('Approve:SopDocument')) {
            throw new WorkflowException(
                message: 'You do not have permission to approve SOP documents.'
            );
        }

        if (! $user->hasRole($this->workflowStep->role)) {
            throw new WorkflowException(
                message: "Only users with the '{$this->workflowStep->role->name}' role can approve this step."
            );
        }

        if ($this->violatesSeparationOfDuties($user)) {
            throw new WorkflowException(
                message: 'You cannot approve this document because of the separation of duties policy.'
            );
        }

        if ($this->violatesDepartmentScope($user)) {
            throw new WorkflowException(
                message: 'You can only approve documents for your own department.'
            );
        }

        return true;
    }

    private function violatesSeparationOfDuties(User $user): bool
    {
        if ($user->hasRole(SopRole::ADMINISTRATOR)) {
            return false;
        }

        return $this->document->created_by === $user->id;
    }

    private function violatesDepartmentScope(User $user): bool
    {
        if ($user->hasRole(SopRole::ADMINISTRATOR)) {
            return false;
        }

        if ($user->department_id === null) {
            return false;
        }

        $requiredDepartmentId = $this->workflowStep->resolveRequiredDepartmentId($this->document->department_id);

        if ($requiredDepartmentId === null) {
            return false;
        }

        return $requiredDepartmentId !== $user->department_id;
    }
}
