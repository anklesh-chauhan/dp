<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\InvestigationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class Investigation extends Model
{
    /** @use HasFactory<InvestigationFactory> */
    use HasFactory;

    protected $fillable = [
        'investigation_number',
        'deviation_id',
        'status',
        'lead_id',
        'methodology',
        'root_cause',
        'conclusion',
        'started_at',
        'due_at',
        'completed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $investigation): void {
            $investigation->investigation_number ??= sprintf(
                'INV-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InvestigationStatus::class,
            'started_at' => 'immutable_datetime',
            'due_at' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Deviation, $this> */
    public function deviation(): BelongsTo
    {
        return $this->belongsTo(Deviation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    /** @return HasMany<Capa, $this> */
    public function capas(): HasMany
    {
        return $this->hasMany(Capa::class);
    }

    /** @return HasMany<InvestigationAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(InvestigationAuditEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
