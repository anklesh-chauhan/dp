<?php

declare(strict_types=1);

namespace App\Domain\TMS\Models;

use App\Domain\TMS\Enums\UserCompetencyStatus;
use App\Domain\TMS\Policies\UserCompetencyPolicy;
use App\Models\User;
use Database\Factories\Domain\TMS\Models\UserCompetencyFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(UserCompetencyPolicy::class)]
class UserCompetency extends Model
{
    /** @use HasFactory<UserCompetencyFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'curriculum_id',
        'status',
        'trained_at',
        'expires_at',
        'verified_by',
        'verified_at',
        'assigned_by',
        'assigned_at',
    ];

    protected $attributes = [
        'status' => UserCompetencyStatus::Assigned->value,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => UserCompetencyStatus::class,
            'trained_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'assigned_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<CompetencyCurriculum, $this> */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(CompetencyCurriculum::class, 'curriculum_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isCurrent(): bool
    {
        if ($this->status !== UserCompetencyStatus::Trained) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
