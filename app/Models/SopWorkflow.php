<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowStepDefinition;
use Database\Factories\SopWorkflowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SopWorkflow extends Model implements ApprovalWorkflowDefinition
{
    /** @use HasFactory<SopWorkflowFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'department_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function approvalWorkflowDefinitionKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    /**
     * @return iterable<ApprovalWorkflowStepDefinition>
     */
    public function approvalWorkflowStepDefinitions(): iterable
    {
        $this->loadMissing('steps.department');

        return $this->steps;
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<SopWorkflowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(SopWorkflowStep::class, 'workflow_id')->orderBy('step_no');
    }
}
