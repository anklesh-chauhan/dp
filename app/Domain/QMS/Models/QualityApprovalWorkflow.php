<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use App\Models\Department;
use Database\Factories\Domain\QMS\Models\QualityApprovalWorkflowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class QualityApprovalWorkflow extends Model implements ApprovalWorkflowDefinition
{
    /** @use HasFactory<QualityApprovalWorkflowFactory> */
    use HasFactory;

    protected $fillable = [
        'workflow_code',
        'name',
        'description',
        'subject_type',
        'department_id',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function approvalWorkflowDefinitionKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    /** @return iterable<ApprovalWorkflowStepDefinition> */
    public function approvalWorkflowStepDefinitions(): iterable
    {
        $this->loadMissing('steps.department');

        return $this->steps;
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<QualityApprovalWorkflowStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(QualityApprovalWorkflowStep::class, 'workflow_id')
            ->orderBy('step_no');
    }

    /** @return HasMany<QualityApprovalInstance, $this> */
    public function approvalInstances(): HasMany
    {
        return $this->hasMany(QualityApprovalInstance::class, 'workflow_id');
    }
}
