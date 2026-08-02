<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentImportItem extends Model
{
    use HasFactory;

    protected $fillable = ['batch_id', 'original_name', 'status', 'mode', 'metadata', 'error_message', 'controlled_document_id', 'created_by'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /** @return BelongsTo<DocumentImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(DocumentImportBatch::class, 'batch_id');
    }

    /** @return HasOne<DocumentOriginalArtifact, $this> */
    public function originalArtifact(): HasOne
    {
        return $this->hasOne(DocumentOriginalArtifact::class, 'import_item_id');
    }
}
