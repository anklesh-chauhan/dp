<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ManagementReviewInputType;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\ManagementReviewType;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ManagementReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class ManagementReview extends Model
{
    /** @use HasFactory<ManagementReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'review_number', 'type', 'status', 'title', 'period_start_at', 'period_end_at',
        'scheduled_at', 'held_at', 'chair_id', 'coordinator_id', 'created_by', 'approved_by',
        'required_inputs', 'input_summary', 'decisions', 'action_summary',
        'minutes_issued_at', 'approved_at', 'completed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $review): void {
            $review->review_number ??= sprintf(
                'MR-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ManagementReviewType::class,
            'status' => ManagementReviewStatus::class,
            'period_start_at' => 'immutable_date',
            'period_end_at' => 'immutable_date',
            'scheduled_at' => 'immutable_datetime',
            'held_at' => 'immutable_datetime',
            'required_inputs' => 'array',
            'minutes_issued_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return list<ManagementReviewInputType> */
    public function requiredInputTypes(): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $value): ?ManagementReviewInputType => is_string($value)
                ? ManagementReviewInputType::tryFrom($value)
                : null,
            $this->required_inputs ?? [],
        )));
    }

    /** @return BelongsTo<User, $this> */
    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_id');
    }

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
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

    /** @return HasMany<ManagementReviewEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ManagementReviewEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
