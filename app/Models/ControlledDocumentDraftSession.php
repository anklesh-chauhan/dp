<?php

namespace App\Models;

use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use Database\Factories\ControlledDocumentDraftSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class ControlledDocumentDraftSession extends Model
{
    /** @use HasFactory<ControlledDocumentDraftSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'created_by',
        'template_id',
        'template_version_id',
        'owner_id',
        'referenced_controlled_document_id',
        'conversation_id',
        'status',
        'title',
        'brief',
        'draft_variables',
        'preview_revision',
        'preview_hash',
        'confirmed_at',
        'controlled_document_id',
    ];

    protected $attributes = [
        'status' => ControlledDocumentDraftSessionStatus::GATHERING->value,
        'preview_revision' => 0,
    ];

    protected static function booted(): void
    {
        self::creating(function (self $session): void {
            $session->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ControlledDocumentDraftSessionStatus::class,
            'brief' => 'array',
            'draft_variables' => 'array',
            'preview_revision' => 'integer',
            'confirmed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<DocumentTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /** @return BelongsTo<DocumentTemplateVersion, $this> */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'template_version_id');
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class);
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function referencedControlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'referenced_controlled_document_id');
    }

    public function calculatePreviewHash(): string
    {
        return hash('sha256', json_encode([
            'template_version_id' => $this->template_version_id,
            'title' => $this->title,
            'brief' => $this->brief,
            'draft_variables' => $this->draft_variables,
            'preview_revision' => $this->preview_revision,
        ], JSON_THROW_ON_ERROR));
    }
}
