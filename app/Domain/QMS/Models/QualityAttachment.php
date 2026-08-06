<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\Shared\Contracts\ContentIntegrityHasher;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\QualityAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

final class QualityAttachment extends Model
{
    /** @use HasFactory<QualityAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'attachment_uuid',
        'disk',
        'path',
        'original_name',
        'annexure_number',
        'attachment_role',
        'is_required',
        'include_in_print',
        'mime_type',
        'size_bytes',
        'content_hash',
        'description',
        'uploaded_by',
        'uploaded_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $attachment): void {
            $attachment->attachment_uuid ??= (string) Str::uuid();
            $attachment->disk ??= 'local';
            $attachment->uploaded_at ??= now();

            if (filled($attachment->path) && Storage::disk($attachment->disk)->exists($attachment->path)) {
                $attachment->size_bytes ??= Storage::disk($attachment->disk)->size($attachment->path);
                $mimeType = Storage::disk($attachment->disk)->mimeType($attachment->path);
                $attachment->mime_type ??= is_string($mimeType) ? $mimeType : null;
                $attachment->content_hash ??= app(ContentIntegrityHasher::class)->hash(
                    (string) $attachment->disk,
                    (string) $attachment->path,
                );
            }
        });
        self::updating(fn () => throw new LogicException('Quality attachments are immutable.'));
        self::deleting(fn () => throw new LogicException('Quality attachments are immutable.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'uploaded_at' => 'immutable_datetime',
            'is_required' => 'boolean',
            'include_in_print' => 'boolean',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
