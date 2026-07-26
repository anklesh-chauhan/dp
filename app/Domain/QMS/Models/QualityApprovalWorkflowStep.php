<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use App\Models\Department;
use Database\Factories\Domain\QMS\Models\QualityApprovalWorkflowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

final class QualityApprovalWorkflowStep extends Model implements ApprovalWorkflowStepDefinition
{
    /** @use HasFactory<QualityApprovalWorkflowStepFactory> */
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_no',
        'role_id',
        'department_id',
        'is_mandatory',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'step_no' => 'integer',
            'is_mandatory' => 'boolean',
        ];
    }

    public function approvalWorkflowStepDefinitionKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function resolveRequiredDepartmentId(?int $subjectDepartmentId): ?int
    {
        return $this->department_id ?? $subjectDepartmentId;
    }

    /** @return BelongsTo<QualityApprovalWorkflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(QualityApprovalWorkflow::class, 'workflow_id');
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<QualityApprovalInstance, $this> */
    public function approvalInstances(): HasMany
    {
        return $this->hasMany(QualityApprovalInstance::class, 'workflow_step_id');
    }
}
