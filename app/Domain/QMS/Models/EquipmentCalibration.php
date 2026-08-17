<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Policies\EquipmentCalibrationPolicy;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\EquipmentCalibrationFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(EquipmentCalibrationPolicy::class)]
final class EquipmentCalibration extends Model
{
    /** @use HasFactory<EquipmentCalibrationFactory> */
    use HasFactory;

    protected $fillable = [
        'calibration_number',
        'equipment_asset_id',
        'status',
        'due_at',
        'performed_at',
        'next_due_at',
        'performed_by',
        'verified_by',
        'result',
        'certificate_reference',
        'notes',
        'deviation_id',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $calibration): void {
            $calibration->calibration_number ??= sprintf(
                'CAL-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => EquipmentCalibrationStatus::class,
            'result' => EquipmentCalibrationResult::class,
            'due_at' => 'immutable_datetime',
            'performed_at' => 'immutable_datetime',
            'next_due_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<EquipmentAsset, $this> */
    public function equipmentAsset(): BelongsTo
    {
        return $this->belongsTo(EquipmentAsset::class);
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return BelongsTo<Deviation, $this> */
    public function deviation(): BelongsTo
    {
        return $this->belongsTo(Deviation::class);
    }

    /** @return HasMany<EquipmentCalibrationEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(EquipmentCalibrationEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
