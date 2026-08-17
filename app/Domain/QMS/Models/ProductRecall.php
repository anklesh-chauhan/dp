<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductRecallType;
use App\Domain\QMS\Policies\ProductRecallPolicy;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ProductRecallFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(ProductRecallPolicy::class)]
final class ProductRecall extends Model
{
    /** @use HasFactory<ProductRecallFactory> */
    use HasFactory;

    protected $fillable = [
        'recall_number',
        'type',
        'status',
        'classification',
        'title',
        'description',
        'product_name',
        'product_code',
        'batch_numbers',
        'market_countries',
        'complaint_id',
        'owner_id',
        'created_by',
        'initiated_at',
        'classified_at',
        'notified_at',
        'executed_at',
        'effectiveness_verified_at',
        'closed_at',
        'effectiveness_summary',
        'is_mock',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $recall): void {
            $recall->recall_number ??= sprintf(
                'RCL-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );

            $type = $recall->type instanceof ProductRecallType
                ? $recall->type
                : ProductRecallType::tryFrom((string) $recall->type);

            if ($type instanceof ProductRecallType) {
                $recall->is_mock = in_array($type, [ProductRecallType::Mock, ProductRecallType::Simulated], true);
            }
        });

        self::updating(function (self $recall): void {
            if ($recall->isDirty('type')) {
                $type = $recall->type instanceof ProductRecallType
                    ? $recall->type
                    : ProductRecallType::tryFrom((string) $recall->type);

                if ($type instanceof ProductRecallType) {
                    $recall->is_mock = in_array($type, [ProductRecallType::Mock, ProductRecallType::Simulated], true);
                }
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ProductRecallType::class,
            'status' => ProductRecallStatus::class,
            'classification' => ProductRecallClassification::class,
            'batch_numbers' => 'array',
            'initiated_at' => 'immutable_datetime',
            'classified_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
            'executed_at' => 'immutable_datetime',
            'effectiveness_verified_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'is_mock' => 'boolean',
        ];
    }

    /** @return BelongsTo<Complaint, $this> */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
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

    /** @return HasMany<ProductRecallEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ProductRecallEvent::class);
    }

    /** @return HasMany<ProductReturn, $this> */
    public function productReturns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
