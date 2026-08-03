<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shared\Contracts\ContentIntegrityHasher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

class DocumentOriginalArtifact extends Model
{
    use HasFactory;

    protected $fillable = ['artifact_uuid', 'import_item_id', 'controlled_document_id', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'preview_path', 'preview_mime_type', 'preview_sha256', 'preview_generated_at', 'preview_error', 'uploaded_at', 'uploaded_by'];

    protected static function booted(): void
    {
        static::creating(function (self $artifact): void {
            $artifact->artifact_uuid ??= (string) Str::uuid();
            $artifact->uploaded_at ??= now();
            if (filled($artifact->path) && Storage::disk($artifact->disk)->exists($artifact->path)) {
                $artifact->size_bytes ??= Storage::disk($artifact->disk)->size($artifact->path);
                $artifact->mime_type ??= Storage::disk($artifact->disk)->mimeType($artifact->path);
                $artifact->sha256 ??= app(ContentIntegrityHasher::class)->hash($artifact->disk, $artifact->path);
            }
        });
        static::updating(fn (): never => throw new LogicException('Original document artifacts are immutable.'));
        static::deleting(fn (): never => throw new LogicException('Original document artifacts are immutable.'));
    }

    /** @return BelongsTo<DocumentImportItem, $this> */
    public function importItem(): BelongsTo
    {
        return $this->belongsTo(DocumentImportItem::class, 'import_item_id');
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class);
    }

    public function linkToControlledDocument(ControlledDocument $document): void
    {
        self::query()->whereKey($this->getKey())->update(['controlled_document_id' => $document->getKey()]);
        $this->setAttribute('controlled_document_id', $document->getKey());
    }
}
