<?php

declare(strict_types=1);

namespace App\Domain\TMS\Models;

use App\Domain\TMS\Policies\TrainingProgramPolicy;
use App\Models\User;
use Database\Factories\Domain\TMS\Models\TrainingProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(TrainingProgramPolicy::class)]
final class TrainingProgram extends Model
{
    /** @use HasFactory<TrainingProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<TrainingProgramItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(TrainingProgramItem::class)->orderBy('sort_order');
    }

    /** @return HasMany<RoleTrainingRequirement, $this> */
    public function roleRequirements(): HasMany
    {
        return $this->hasMany(RoleTrainingRequirement::class);
    }

    /** @return HasMany<TrainingAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
