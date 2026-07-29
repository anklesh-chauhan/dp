<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopAuditLog extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_PUBLISHED = 'published';

    public const ACTION_ARCHIVED = 'archived';

    public const ACTION_MARKED_OBSOLETE = 'marked_obsolete';

    public const ACTION_SUPERSEDED = 'superseded';

    public const ACTION_RETENTION_COMPLETED = 'retention_completed';

    public const ACTION_DESTROYED = 'destroyed';

    public const ACTION_VERSION_CREATED = 'version_created';

    public const ACTION_VERSION_UPDATED = 'version_updated';

    public const ACTION_VERSION_PUBLISHED = 'version_published';

    public const ACTION_DOCUMENT_REVISION_CREATED = 'document_revision_created';

    public const ACTION_GENERATED_SOP = 'generated_sop';

    public const ACTION_SUBMITTED = 'submitted';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_RETURNED = 'returned';

    public const ACTION_LOCKED = 'locked';

    public const ACTION_UNLOCKED = 'unlocked';

    public const ACTION_ISSUED = 'issued';

    public const ACTION_RECALLED = 'recalled';

    public const ACTION_COPY_DESTROYED = 'copy_destroyed';

    public const ACTION_DOWNLOADED = 'downloaded';

    public const ACTION_PRINTED = 'printed';

    public $timestamps = true;

    protected $fillable = [
        'document_id',
        'document_template_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
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
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
