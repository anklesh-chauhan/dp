<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DocumentImportBatch extends Model
{
    use HasFactory;

    protected $fillable = ['batch_uuid', 'name', 'status', 'source_type', 'total_items', 'successful_items', 'failed_items', 'created_by', 'started_at', 'completed_at', 'error_summary'];

    protected static function booted(): void
    {
        static::creating(fn (self $batch): ?string => $batch->batch_uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }

    /** @return HasMany<DocumentImportItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentImportItem::class, 'batch_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
