<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentExecutionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DocumentExecutionItem extends Model
{
    /** @use HasFactory<DocumentExecutionItemFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if ($item->exists && ! $item->section()->firstOrFail()->execution()->firstOrFail()->isEditable()) {
                throw new LogicException('A completed or reviewed execution record cannot be changed.');
            }

            if (in_array(strtolower(trim((string) $item->response)), ['n/a', 'na'], true) && blank($item->comments)) {
                throw new LogicException('An explanation is required for an N/A response.');
            }

            $item->calculateResultStatus();

            if ($item->isComplete()) {
                $item->completed_at ??= now();
                $item->completed_by ??= auth()->id();
                $item->verified_at = filled($item->verified_by) ? ($item->verified_at ?? now()) : null;
            }
        });
    }

    protected $fillable = [
        'document_execution_section_id', 'source_item_id', 'item_order', 'scheduled_at',
        'label', 'value_type', 'unit', 'decimal_precision', 'acceptance_operator',
        'acceptance_min', 'acceptance_max', 'response', 'result_status', 'comments',
        'is_required', 'completed_by', 'completed_at', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'item_order' => 'integer',
            'scheduled_at' => 'datetime',
            'decimal_precision' => 'integer',
            'acceptance_min' => 'decimal:8',
            'acceptance_max' => 'decimal:8',
            'is_required' => 'boolean',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(DocumentExecutionSection::class, 'document_execution_section_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isComplete(): bool
    {
        return ! $this->is_required || filled($this->response);
    }

    public function isIndependentlyVerified(): bool
    {
        return $this->isComplete() && $this->verified_by !== null && $this->verified_by !== $this->completed_by;
    }

    public function calculateResultStatus(): void
    {
        if ($this->value_type !== 'numeric' || blank($this->response)) {
            $this->result_status = null;

            return;
        }

        if (! is_numeric($this->response)) {
            $this->result_status = 'fail';

            return;
        }

        $value = (float) $this->response;
        $minimum = $this->acceptance_min === null ? null : (float) $this->acceptance_min;
        $maximum = $this->acceptance_max === null ? null : (float) $this->acceptance_max;

        $this->result_status = match ($this->acceptance_operator) {
            'between' => $minimum !== null && $maximum !== null && $value >= $minimum && $value <= $maximum ? 'pass' : 'fail',
            'gte' => $minimum !== null && $value >= $minimum ? 'pass' : 'fail',
            'lte' => $maximum !== null && $value <= $maximum ? 'pass' : 'fail',
            'eq' => $minimum !== null && $value === $minimum ? 'pass' : 'fail',
            default => null,
        };
    }
}
