<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Enums\ProductQualityReviewType;
use App\Domain\QMS\Policies\ProductQualityReviewPolicy;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ProductQualityReviewFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(ProductQualityReviewPolicy::class)]
final class ProductQualityReview extends Model
{
    /** @use HasFactory<ProductQualityReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'review_number', 'type', 'status', 'title', 'product_name', 'product_code', 'dosage_form',
        'period_start_at', 'period_end_at', 'owner_id', 'created_by', 'approved_by',
        'input_summary', 'conclusions', 'recommendations', 'yield_summary', 'reject_summary',
        'started_at', 'approved_at', 'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $review): void {
            $review->review_number ??= sprintf(
                'PQR-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ProductQualityReviewType::class,
            'status' => ProductQualityReviewStatus::class,
            'period_start_at' => 'immutable_date',
            'period_end_at' => 'immutable_date',
            'started_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
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

    /** @return HasMany<ProductQualityReviewEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ProductQualityReviewEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
