<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopWorkflowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class SopWorkflowStep extends Model
{
    /** @use HasFactory<SopWorkflowStepFactory> */
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_no',
        'role_id',
        'approval_step_type_id',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'step_no' => 'integer',
            'is_mandatory' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SopWorkflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(SopWorkflow::class, 'workflow_id');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<ApprovalStepType, $this>
     */
    public function approvalStepType(): BelongsTo
    {
        return $this->belongsTo(ApprovalStepType::class);
    }

    /**
     * @return HasMany<SopApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(SopApproval::class, 'workflow_step_id');
    }
}
