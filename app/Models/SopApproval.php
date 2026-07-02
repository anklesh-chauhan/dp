<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\SopRole;
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
        'decision',
        'comments',
        'approved_at',
        'signature_hash',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
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
     * @param  Builder<SopApproval>  $query
     * @return Builder<SopApproval>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('decision', ApprovalDecision::Pending);
    }

    /**
     * @param  Builder<SopApproval>  $query
     * @return Builder<SopApproval>
     */
    public function scopeActionableFor(Builder $query, User $user): Builder
    {
        return $query->pending()
            ->whereHas('document', function (Builder $documentQuery) use ($user): void {
                if ($user->department_id !== null && ! $user->hasRole(SopRole::Administrator->value)) {
                    $documentQuery->where('department_id', $user->department_id);
                }
            });
    }

    public function isActionable(): bool
    {
        if ($this->decision !== ApprovalDecision::Pending) {
            return false;
        }

        $this->loadMissing(['document.approvals.workflowStep', 'workflowStep']);

        $previousMandatoryStepsPending = $this->document->approvals
            ->filter(fn (SopApproval $approval): bool => $approval->workflowStep->step_no < $this->workflowStep->step_no
                && $approval->workflowStep->is_mandatory)
            ->contains(fn (SopApproval $approval): bool => $approval->decision !== ApprovalDecision::Approved);

        return ! $previousMandatoryStepsPending;
    }

    public function canBeApprovedBy(User $user): bool
    {
        if (! $this->isActionable()) {
            return false;
        }

        $this->loadMissing(['workflowStep.role', 'document']);

        if (! $user->can('Approve:SopDocument')) {
            return false;
        }

        if (! $user->hasRole($this->workflowStep->role)) {
            return false;
        }

        if ($this->violatesSeparationOfDuties($user)) {
            return false;
        }

        if ($this->violatesDepartmentScope($user)) {
            return false;
        }

        return true;
    }

    private function violatesSeparationOfDuties(User $user): bool
    {
        if ($user->hasRole(SopRole::Administrator->value)) {
            return false;
        }

        return $this->document->created_by === $user->id;
    }

    private function violatesDepartmentScope(User $user): bool
    {
        if ($user->hasRole(SopRole::Administrator->value)) {
            return false;
        }

        if ($user->department_id === null) {
            return false;
        }

        return $this->document->department_id !== $user->department_id;
    }
}
