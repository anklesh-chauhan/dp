<?php

declare(strict_types=1);

namespace App\Domain\TMS\Models;

use App\Models\Designation;
use Database\Factories\Domain\TMS\Models\RoleTrainingRequirementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RoleTrainingRequirement extends Model
{
    /** @use HasFactory<RoleTrainingRequirementFactory> */
    use HasFactory;

    protected $fillable = [
        'designation_id',
        'training_program_id',
        'is_required',
    ];

    protected $attributes = [
        'is_required' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    /** @return BelongsTo<Designation, $this> */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /** @return BelongsTo<TrainingProgram, $this> */
    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }
}
