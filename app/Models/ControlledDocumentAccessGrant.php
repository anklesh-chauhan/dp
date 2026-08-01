<?php

namespace App\Models;

use Database\Factories\ControlledDocumentAccessGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentAccessGrant extends Model
{
    /** @use HasFactory<ControlledDocumentAccessGrantFactory> */
    use HasFactory;

    protected $fillable = [
        'controlled_document_id',
        'user_id',
        'can_view',
        'can_print',
        'can_download',
        'expires_at',
        'granted_by',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_print' => 'boolean',
            'can_download' => 'boolean',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'controlled_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
