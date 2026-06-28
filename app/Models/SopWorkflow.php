<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopWorkflowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SopWorkflow extends Model
{
    /** @use HasFactory<SopWorkflowFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SopWorkflowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(SopWorkflowStep::class, 'workflow_id')->orderBy('step_no');
    }
}
