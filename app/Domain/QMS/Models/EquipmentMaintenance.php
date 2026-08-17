<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Policies\EquipmentMaintenancePolicy;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\EquipmentMaintenanceFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UsePolicy(EquipmentMaintenancePolicy::class)]
final class EquipmentMaintenance extends Model
{
    /** @use HasFactory<EquipmentMaintenanceFactory> */
    use HasFactory;

    protected $fillable = [
        'work_order_number',
        'equipment_asset_id',
        'status',
        'due_at',
        'completed_at',
        'description',
        'performed_by',
        'notes',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $maintenance): void {
            $maintenance->work_order_number ??= sprintf(
                'PM-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => EquipmentMaintenanceStatus::class,
            'due_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
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

    /** @return HasMany<EquipmentMaintenanceEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceEvent::class);
    }
}
