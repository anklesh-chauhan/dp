<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentIssuanceBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class DocumentIssuanceBatch extends Model
{
    public const PACK_PENDING = 'pending';

    public const PACK_READY = 'ready';

    public const PACK_FAILED = 'failed';

    public const MAX_PAPER_COPIES = 200;

    public const SYNC_PACK_THRESHOLD = 5;

    /** @use HasFactory<DocumentIssuanceBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'issued_by',
        'copy_count',
        'first_issuance_number',
        'last_issuance_number',
        'issuance_ids',
        'pack_status',
        'pack_disk',
        'pack_path',
        'pack_filename',
        'pack_sha256',
        'pack_size_bytes',
        'pack_error',
        'generated_by',
        'generated_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'pack_status' => self::PACK_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'copy_count' => 'integer',
            'issuance_ids' => 'array',
            'pack_size_bytes' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ControlledDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasMany<DocumentIssuance, $this>
     */
    public function issuances(): HasMany
    {
        return $this->hasMany(DocumentIssuance::class, 'issuance_batch_id')->orderBy('copy_number');
    }

    /**
     * @return Collection<int, DocumentIssuance>
     */
    public function printableCopies(): Collection
    {
        $ids = collect($this->issuance_ids ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->values()
            ->all();

        if ($ids !== []) {
            return DocumentIssuance::query()
                ->whereIn('id', $ids)
                ->orderBy('copy_number')
                ->get();
        }

        $this->loadMissing('issuances');

        return $this->issuances->sortBy('copy_number')->values();
    }

    public function isPackReady(): bool
    {
        return $this->pack_status === self::PACK_READY
            && filled($this->pack_path)
            && filled($this->pack_sha256);
    }

    public function isPackPending(): bool
    {
        return $this->pack_status === self::PACK_PENDING;
    }
}
