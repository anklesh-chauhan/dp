<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityAttachment;
use Database\Factories\DocumentExecutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DocumentExecution extends Model
{
    /** @use HasFactory<DocumentExecutionFactory> */
    use HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_QA_REVIEW = 'qa_review';

    public const STATUS_CLOSED = 'closed';

    public const DISPOSITION_NOT_APPLICABLE = 'not_applicable';

    public const DISPOSITION_PENDING = 'pending';

    public const DISPOSITION_RELEASED = 'released';

    public const DISPOSITION_REJECTED = 'rejected';

    protected $fillable = [
        'document_issuance_id', 'execution_number', 'document_number', 'document_version',
        'document_type_code', 'workflow_configuration', 'status', 'batch_number',
        'product_name', 'log_frequency', 'log_period_start', 'log_period_end',
        'supervisor_id', 'completed_by', 'completed_at', 'reviewed_by', 'reviewed_at',
        'review_notes', 'qa_approved_by', 'qa_approved_at', 'qa_notes', 'disposition', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'document_version' => 'integer',
            'workflow_configuration' => 'array',
            'log_period_start' => 'date',
            'log_period_end' => 'date',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'qa_approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function issuance(): BelongsTo
    {
        return $this->belongsTo(DocumentIssuance::class, 'document_issuance_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(DocumentExecutionSection::class)->orderBy('section_order');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(DocumentExecutionMaterial::class)->orderBy('material_order');
    }

    /** @return BelongsToMany<Deviation, $this> */
    public function deviations(): BelongsToMany
    {
        return $this->belongsToMany(Deviation::class, 'document_execution_deviations');
    }

    public function materialsAreReconciled(): bool
    {
        $materials = $this->relationLoaded('materials') ? $this->materials : $this->materials()->get();

        return $materials->isNotEmpty() && $materials->every->isReconciled();
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function qaApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qa_approved_by');
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }

    public function isEditable(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_UNDER_REVIEW, self::STATUS_QA_REVIEW, self::STATUS_CLOSED], true);
    }

    public function requires(string $control): bool
    {
        return (bool) ($this->workflow_configuration[$control] ?? false);
    }

    /** @return array{total: int, completed: int, verified: int, pending: int} */
    public function executionSummary(): array
    {
        $sections = $this->relationLoaded('sections') ? $this->sections : $this->sections()->get();
        $completed = $sections->filter->isCompleted()->count();
        $verified = $sections->filter->isIndependentlyVerified()->count();

        return [
            'total' => $sections->count(),
            'completed' => $completed,
            'verified' => $verified,
            'pending' => max(0, $sections->count() - $completed),
        ];
    }
}
