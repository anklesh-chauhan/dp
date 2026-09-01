<?php

declare(strict_types=1);

namespace App\Domain\TMS\Models;

use App\Domain\Shared\Support\CompetencyGateKey;
use App\Domain\TMS\Policies\CompetencyCurriculumPolicy;
use App\Models\SopRole;
use App\Models\User;
use Database\Factories\Domain\TMS\Models\CompetencyCurriculumFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(CompetencyCurriculumPolicy::class)]
class CompetencyCurriculum extends Model
{
    /** @use HasFactory<CompetencyCurriculumFactory> */
    use HasFactory;

    public const GATE_DOCUMENT_EXECUTION_QA = CompetencyGateKey::DOCUMENT_EXECUTION_QA;

    public const GATE_CHANGE_CONTROL_APPROVE = CompetencyGateKey::CHANGE_CONTROL_APPROVE;

    protected $table = 'competency_curricula';

    protected $fillable = [
        'code',
        'name',
        'role_name',
        'sop_role_id',
        'description',
        'requalification_months',
        'gate_key',
        'is_active',
        'created_by',
    ];

    protected $attributes = [
        'is_active' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'requalification_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<SopRole, $this> */
    public function sopRole(): BelongsTo
    {
        return $this->belongsTo(SopRole::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<CompetencyCurriculumItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CompetencyCurriculumItem::class, 'curriculum_id')
            ->orderBy('sort_order');
    }

    /** @return HasMany<UserCompetency, $this> */
    public function userCompetencies(): HasMany
    {
        return $this->hasMany(UserCompetency::class, 'curriculum_id');
    }

    public function roleLabel(): string
    {
        if (filled($this->role_name)) {
            return (string) $this->role_name;
        }

        $this->loadMissing('sopRole');

        return (string) ($this->sopRole?->name ?? 'Unassigned role');
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function forGate(Builder $query, string $gateKey): void
    {
        $query->where('gate_key', $gateKey);
    }
}
