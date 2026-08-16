<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ControlledDocumentTrainingAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentTrainingAssignment extends Model
{
    /** @use HasFactory<ControlledDocumentTrainingAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'completed_at',
        'completion_comments',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
