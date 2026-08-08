<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentExecutionSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class DocumentExecutionSection extends Model
{
    /** @use HasFactory<DocumentExecutionSectionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $section): void {
            if ($section->exists && ! $section->execution()->firstOrFail()->isEditable()) {
                throw new LogicException('A completed or reviewed execution record cannot be changed.');
            }

            if ($section->isCompleted()) {
                $section->completed_at ??= now();
                $section->completed_by ??= auth()->id();
                $section->verified_at = filled($section->verified_by) ? ($section->verified_at ?? now()) : null;
            }
        });
    }

    protected $fillable = [
        'document_execution_id', 'source_section_id', 'title', 'section_order', 'section_type',
        'content', 'configuration', 'status', 'completion_notes', 'completed_by',
        'completed_at', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
            'configuration' => 'array',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(DocumentExecution::class, 'document_execution_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentExecutionItem::class)->orderBy('item_order');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'not_applicable'], true);
    }

    public function isIndependentlyVerified(): bool
    {
        return $this->isCompleted() && $this->verified_by !== null && $this->verified_by !== $this->completed_by;
    }
}
