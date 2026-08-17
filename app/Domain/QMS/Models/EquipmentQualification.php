<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Enums\EquipmentQualificationType;
use App\Domain\QMS\Policies\EquipmentQualificationPolicy;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\EquipmentQualificationFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(EquipmentQualificationPolicy::class)]
final class EquipmentQualification extends Model
{
    /** @use HasFactory<EquipmentQualificationFactory> */
    use HasFactory;

    protected $fillable = [
        'qualification_number',
        'equipment_asset_id',
        'type',
        'status',
        'protocol_title',
        'protocol_summary',
        'acceptance_criteria',
        'executed_by',
        'reviewed_by',
        'deviation_id',
        'started_at',
        'completed_at',
        'approved_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $qualification): void {
            $qualification->qualification_number ??= sprintf(
                'EQ-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => EquipmentQualificationType::class,
            'status' => EquipmentQualificationStatus::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<EquipmentAsset, $this> */
    public function equipmentAsset(): BelongsTo
    {
        return $this->belongsTo(EquipmentAsset::class);
    }

    /** @return BelongsTo<User, $this> */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<Deviation, $this> */
    public function deviation(): BelongsTo
    {
        return $this->belongsTo(Deviation::class);
    }

    /** @return HasMany<EquipmentQualificationEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(EquipmentQualificationEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
