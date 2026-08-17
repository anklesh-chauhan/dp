<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Policies\ValidationMasterPlanPolicy;
use App\Models\ControlledDocument;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ValidationMasterPlanFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(ValidationMasterPlanPolicy::class)]
final class ValidationMasterPlan extends Model
{
    /** @use HasFactory<ValidationMasterPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'vmp_number',
        'title',
        'status',
        'period_start_at',
        'period_end_at',
        'scope',
        'owner_id',
        'created_by',
        'approved_by',
        'controlled_document_id',
        'approved_at',
        'retired_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $plan): void {
            $plan->vmp_number ??= sprintf(
                'VMP-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ValidationMasterPlanStatus::class,
            'period_start_at' => 'immutable_datetime',
            'period_end_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'retired_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class);
    }

    /** @return HasMany<ValidationMasterPlanEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ValidationMasterPlanEvent::class);
    }

    /** @return HasMany<EquipmentAsset, $this> */
    public function equipmentAssets(): HasMany
    {
        return $this->hasMany(EquipmentAsset::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
