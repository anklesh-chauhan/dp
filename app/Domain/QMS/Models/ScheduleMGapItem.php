<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Policies\ScheduleMGapItemPolicy;
use App\Models\ControlledDocument;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ScheduleMGapItemFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[UsePolicy(ScheduleMGapItemPolicy::class)]
final class ScheduleMGapItem extends Model
{
    /** @use HasFactory<ScheduleMGapItemFactory> */
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'part_code',
        'clause_ref',
        'clause_title',
        'status',
        'evidence_notes',
        'evidence_document_id',
        'evidence_qms_type',
        'evidence_qms_id',
        'owner_id',
        'due_at',
        'closed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ScheduleMGapItemStatus::class,
            'due_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ScheduleMGapAssessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ScheduleMGapAssessment::class, 'assessment_id');
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'evidence_document_id');
    }

    /** @return MorphTo<Model, $this> */
    public function evidenceQms(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
