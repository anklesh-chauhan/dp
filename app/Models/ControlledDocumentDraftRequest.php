<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AI\Enums\ControlledDocumentDraftRequestStatus;
use Database\Factories\ControlledDocumentDraftRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class ControlledDocumentDraftRequest extends Model
{
    /** @use HasFactory<ControlledDocumentDraftRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'controlled_document_draft_session_id',
        'requested_by',
        'message',
        'status',
        'initial_preview_revision',
        'preview_hash',
        'failure_message',
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $attributes = [
        'status' => ControlledDocumentDraftRequestStatus::QUEUED->value,
    ];

    protected static function booted(): void
    {
        self::creating(function (self $request): void {
            $request->uuid ??= (string) Str::uuid();
            $request->queued_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ControlledDocumentDraftRequestStatus::class,
            'initial_preview_revision' => 'integer',
            'queued_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ControlledDocumentDraftSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(
            ControlledDocumentDraftSession::class,
            'controlled_document_draft_session_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
