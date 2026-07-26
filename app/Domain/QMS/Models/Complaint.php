<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ComplaintSource;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    protected $fillable = [
        'complaint_number',
        'status',
        'source',
        'type',
        'title',
        'description',
        'external_reference',
        'received_at',
        'received_by',
        'department_id',
        'owner_id',
        'product_name',
        'batch_number',
        'market_country_code',
        'adverse_event_suspected',
        'regulatory_reportable',
        'regulatory_authority',
        'regulatory_report_due_at',
        'regulatory_reported_at',
        'regulatory_reference',
        'response_due_at',
        'acknowledged_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $complaint): void {
            $complaint->complaint_number ??= sprintf(
                'CMP-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
            $complaint->market_country_code = filled($complaint->market_country_code)
                ? Str::upper((string) $complaint->market_country_code)
                : null;
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
            'source' => ComplaintSource::class,
            'type' => ComplaintType::class,
            'received_at' => 'immutable_datetime',
            'adverse_event_suspected' => 'boolean',
            'regulatory_reportable' => 'boolean',
            'regulatory_report_due_at' => 'immutable_date',
            'regulatory_reported_at' => 'immutable_datetime',
            'response_due_at' => 'immutable_date',
            'acknowledged_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
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

    /** @return HasMany<ComplaintAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ComplaintAuditEvent::class);
    }

    /** @return HasMany<Deviation, $this> */
    public function deviations(): HasMany
    {
        return $this->hasMany(Deviation::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
