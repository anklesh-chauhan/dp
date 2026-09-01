<?php

declare(strict_types=1);

namespace App\Domain\TMS\Models;

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Policies\TrainingAssignmentPolicy;
use App\Models\ControlledDocument;
use App\Models\User;
use Database\Factories\TrainingAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(TrainingAssignmentPolicy::class)]
class TrainingAssignment extends Model
{
    /** @use HasFactory<TrainingAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'source_type',
        'user_id',
        'controlled_document_id',
        'training_program_id',
        'assigned_by',
        'assigned_at',
        'completed_at',
        'completion_comments',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => TrainingAssignmentSource::class,
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TrainingAssignment $assignment): void {
            if ($assignment->controlled_document_id !== null && $assignment->source_type === null) {
                $assignment->source_type = TrainingAssignmentSource::ControlledDocument;
            }

            if ($assignment->training_program_id !== null && $assignment->source_type === null) {
                $assignment->source_type = TrainingAssignmentSource::TrainingProgram;
            }
        });
    }

    public function getDocumentIdAttribute(): ?int
    {
        return $this->controlled_document_id === null ? null : (int) $this->controlled_document_id;
    }

    public function setDocumentIdAttribute(mixed $value): void
    {
        $this->controlled_document_id = $value === null ? null : (int) $value;
        $this->source_type = TrainingAssignmentSource::ControlledDocument;
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'controlled_document_id');
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->controlledDocument();
    }

    /** @return BelongsTo<TrainingProgram, $this> */
    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isCompleted();
    }
}
