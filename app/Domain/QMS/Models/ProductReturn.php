<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Concerns\ProvidesNarrativeSignatureContent;
use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Policies\ProductReturnPolicy;
use App\Domain\Shared\Contracts\ProvidesElectronicSignatureContent;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ProductReturnFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(ProductReturnPolicy::class)]
final class ProductReturn extends Model implements ProvidesElectronicSignatureContent
{
    /** @use HasFactory<ProductReturnFactory> */
    use HasFactory;

    use ProvidesNarrativeSignatureContent;

    protected $fillable = [
        'return_number',
        'status',
        'disposition',
        'product_name',
        'batch_number',
        'quantity',
        'unit',
        'reason',
        'source',
        'product_recall_id',
        'owner_id',
        'created_by',
        'received_at',
        'quarantined_at',
        'dispositioned_at',
        'closed_at',
        'qa_disposition_notes',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $return): void {
            $return->return_number ??= sprintf(
                'PRT-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProductReturnStatus::class,
            'disposition' => ProductReturnDisposition::class,
            'quantity' => 'decimal:3',
            'received_at' => 'immutable_datetime',
            'quarantined_at' => 'immutable_datetime',
            'dispositioned_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ProductRecall, $this> */
    public function productRecall(): BelongsTo
    {
        return $this->belongsTo(ProductRecall::class);
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

    /** @return HasMany<ProductReturnEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ProductReturnEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
