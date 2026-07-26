<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\SupplierCategory;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Enums\SupplierRiskLevel;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\SupplierQualificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class SupplierQualification extends Model
{
    /** @use HasFactory<SupplierQualificationFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_number', 'legal_name', 'site_name', 'category', 'status', 'risk_level',
        'material_service_scope', 'country_code', 'site_address', 'contact_name',
        'contact_email', 'contact_phone', 'department_id', 'owner_id', 'created_by',
        'approved_by', 'qualification_rationale', 'qualification_started_at',
        'audit_due_at', 'audit_completed_at', 'qualified_at', 'qualification_expires_at',
        'next_review_at', 'suspended_at', 'disqualified_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $qualification): void {
            $qualification->supplier_number ??= sprintf(
                'SUP-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
            $qualification->country_code = filled($qualification->country_code)
                ? Str::upper((string) $qualification->country_code)
                : null;
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => SupplierCategory::class,
            'status' => SupplierQualificationStatus::class,
            'risk_level' => SupplierRiskLevel::class,
            'qualification_started_at' => 'immutable_datetime',
            'audit_due_at' => 'immutable_date',
            'audit_completed_at' => 'immutable_datetime',
            'qualified_at' => 'immutable_datetime',
            'qualification_expires_at' => 'immutable_date',
            'next_review_at' => 'immutable_date',
            'suspended_at' => 'immutable_datetime',
            'disqualified_at' => 'immutable_datetime',
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

    /** @return HasMany<SupplierQualificationEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(SupplierQualificationEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
