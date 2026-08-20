<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Concerns\ProvidesNarrativeSignatureContent;
use App\Domain\QMS\Enums\EquipmentAssetCategory;
use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Policies\EquipmentAssetPolicy;
use App\Domain\Shared\Contracts\ProvidesElectronicSignatureContent;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\EquipmentAssetFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UsePolicy(EquipmentAssetPolicy::class)]
final class EquipmentAsset extends Model implements ProvidesElectronicSignatureContent
{
    /** @use HasFactory<EquipmentAssetFactory> */
    use HasFactory;

    use ProvidesNarrativeSignatureContent;

    protected $fillable = [
        'asset_number',
        'name',
        'asset_tag',
        'location',
        'category',
        'criticality',
        'gxp_impact',
        'status',
        'department_id',
        'owner_id',
        'validation_master_plan_id',
        'manufacturer',
        'model',
        'serial_number',
        'installed_at',
        'commissioned_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $asset): void {
            $asset->asset_number ??= sprintf(
                'EQA-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => EquipmentAssetCategory::class,
            'criticality' => EquipmentAssetCriticality::class,
            'status' => EquipmentAssetStatus::class,
            'gxp_impact' => 'boolean',
            'installed_at' => 'immutable_datetime',
            'commissioned_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<ValidationMasterPlan, $this> */
    public function validationMasterPlan(): BelongsTo
    {
        return $this->belongsTo(ValidationMasterPlan::class);
    }

    /** @return HasMany<EquipmentAssetEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(EquipmentAssetEvent::class);
    }

    /** @return HasMany<EquipmentQualification, $this> */
    public function qualifications(): HasMany
    {
        return $this->hasMany(EquipmentQualification::class);
    }

    /** @return HasMany<EquipmentCalibration, $this> */
    public function calibrations(): HasMany
    {
        return $this->hasMany(EquipmentCalibration::class);
    }

    /** @return HasMany<EquipmentMaintenance, $this> */
    public function maintenances(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class);
    }
}
