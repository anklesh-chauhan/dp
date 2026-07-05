<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsLookupModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStepType extends Model
{
    use IsLookupModel;

    public const CHECKER = 'checker';
    public const REVIEW = 'review';
    public const QA_REVIEW = 'qa_review';
    public const APPROVER = 'approver';
    public const APPROVAL = 'approval';

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<SopWorkflowStep, $this>
     */
    public function workflowSteps(): HasMany
    {
        return $this->hasMany(SopWorkflowStep::class);
    }
}
