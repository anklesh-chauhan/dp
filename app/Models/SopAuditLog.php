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
    public const ACTION_GENERATED_SOP = 'generated_sop';
    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_RETURNED = 'returned';
    public const ACTION_DOWNLOADED = 'downloaded';
    public const ACTION_PRINTED = 'printed';

    public $timestamps = true;

    protected $fillable = [
        'document_id',
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
     * @return BelongsTo<SopDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
