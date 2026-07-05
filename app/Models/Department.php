<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * @return HasMany<SopTemplate, $this>
     */
    public function sopTemplates(): HasMany
    {
        return $this->hasMany(SopTemplate::class);
    }

    /**
     * @return HasMany<SopDocument, $this>
     */
    public function sopDocuments(): HasMany
    {
        return $this->hasMany(SopDocument::class);
    }

    /**
     * @return HasMany<SopWorkflowStep, $this>
     */
    public function workflowSteps(): HasMany
    {
        return $this->hasMany(SopWorkflowStep::class);
    }
}
