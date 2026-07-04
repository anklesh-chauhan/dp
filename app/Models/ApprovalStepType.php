<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsLookupModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStepType extends Model
{
    use IsLookupModel;

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<SopWorkflowStep, $this>
     */
    public function workflowSteps(): HasMany
    {
        return $this->hasMany(SopWorkflowStep::class);
    }
}
