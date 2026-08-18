<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Policies\BatchReleasePolicy;
use App\Models\DocumentExecution;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\BatchReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UsePolicy(BatchReleasePolicy::class)]
final class BatchRelease extends Model
{
    /** @use HasFactory<BatchReleaseFactory> */
    use HasFactory;

    protected static function newFactory(): BatchReleaseFactory
    {
        return BatchReleaseFactory::new();
    }

    protected $fillable = [
        'release_number', 'batch_number', 'product_name', 'status', 'document_execution_id',
        'disposition_rationale', 'owner_id', 'created_by', 'released_by', 'released_at', 'rejected_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $release): void {
            $release->release_number ??= sprintf(
                'BREL-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => BatchReleaseStatus::class,
            'released_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
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
    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /** @return BelongsTo<DocumentExecution, $this> */
    public function documentExecution(): BelongsTo
    {
        return $this->belongsTo(DocumentExecution::class);
    }

    /** @return HasMany<BatchReleaseEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(BatchReleaseEvent::class);
    }
}
