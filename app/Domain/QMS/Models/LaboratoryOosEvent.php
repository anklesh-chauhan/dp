<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Concerns\ProvidesNarrativeSignatureContent;
use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Enums\LaboratoryOosType;
use App\Domain\QMS\Policies\LaboratoryOosEventPolicy;
use App\Domain\Shared\Contracts\ProvidesElectronicSignatureContent;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\LaboratoryOosEventFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use LogicException;

#[UsePolicy(LaboratoryOosEventPolicy::class)]
final class LaboratoryOosEvent extends Model implements ProvidesElectronicSignatureContent
{
    /** @use HasFactory<LaboratoryOosEventFactory> */
    use HasFactory;

    use ProvidesNarrativeSignatureContent;

    protected $fillable = [
        'event_number',
        'type',
        'status',
        'title',
        'test_name',
        'method_reference',
        'sample_id',
        'batch_number',
        'product_name',
        'specification_limit',
        'observed_result',
        'unit',
        'phase_one_outcome',
        'phase_one_notes',
        'phase_two_outcome',
        'phase_two_notes',
        'hypothesis',
        'invalidation_justification',
        'investigation_id',
        'deviation_id',
        'department_id',
        'owner_id',
        'created_by',
        'analyst_id',
        'started_at',
        'phase_one_completed_at',
        'phase_two_completed_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $event): void {
            $type = $event->type instanceof LaboratoryOosType
                ? $event->type
                : (LaboratoryOosType::tryFrom((string) ($event->type ?? '')) ?? LaboratoryOosType::Oos);

            $event->event_number ??= sprintf(
                '%s-%s-%s',
                $type === LaboratoryOosType::Oot ? 'OOT' : 'OOS',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });

        self::updating(function (self $event): void {
            if ($event->isDirty('deviation_id') && $event->getOriginal('deviation_id') !== null) {
                throw new LogicException('A laboratory OOS deviation link is immutable.');
            }

            if ($event->isDirty('investigation_id') && $event->getOriginal('investigation_id') !== null) {
                throw new LogicException('A laboratory OOS investigation link is immutable.');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => LaboratoryOosType::class,
            'status' => LaboratoryOosStatus::class,
            'phase_one_outcome' => LaboratoryOosPhaseOutcome::class,
            'phase_two_outcome' => LaboratoryOosPhaseOutcome::class,
            'started_at' => 'immutable_datetime',
            'phase_one_completed_at' => 'immutable_datetime',
            'phase_two_completed_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
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
    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    /** @return BelongsTo<Investigation, $this> */
    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    /** @return BelongsTo<Deviation, $this> */
    public function deviation(): BelongsTo
    {
        return $this->belongsTo(Deviation::class);
    }

    /** @return HasMany<LaboratoryOosEventEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(LaboratoryOosEventEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
