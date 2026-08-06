<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentSectionItem extends Model
{
    public const VALUE_TEXT = 'text';

    public const VALUE_NUMERIC = 'numeric';

    public const VALUE_BOOLEAN = 'boolean';

    public const RESULT_PASS = 'pass';

    public const RESULT_FAIL = 'fail';

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->calculateResultStatus();
            if ($item->isComplete()) {
                $item->completed_at ??= now();
                $item->completed_by ??= auth()->id();
            } else {
                $item->completed_at = null;
                $item->completed_by = null;
                $item->verified_at = null;
            }

            $item->verified_at = filled($item->verified_by) && $item->isComplete() ? ($item->verified_at ?? now()) : null;
        });
    }

    protected $fillable = [
        'section_id',
        'item_order',
        'scheduled_at',
        'label',
        'value_type',
        'unit',
        'decimal_precision',
        'acceptance_operator',
        'acceptance_min',
        'acceptance_max',
        'response',
        'result_status',
        'comments',
        'is_required',
        'completed_by',
        'completed_at',
        'verified_by',
        'verified_at',
        'review_status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'item_order' => 'integer',
            'is_required' => 'boolean',
            'decimal_precision' => 'integer',
            'acceptance_min' => 'decimal:8',
            'acceptance_max' => 'decimal:8',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ControlledDocumentSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ControlledDocumentSection::class, 'section_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isComplete(): bool
    {
        return ! $this->is_required || filled($this->response);
    }

    public function isIndependentlyVerified(): bool
    {
        return $this->isComplete()
            && $this->verified_by !== null
            && $this->verified_by !== $this->completed_by;
    }

    public function responseIsValidFor(string $sectionType): bool
    {
        if (! $this->is_required && blank($this->response)) {
            return true;
        }

        if (blank($this->response)) {
            return false;
        }

        return $sectionType !== ControlledDocumentSection::TYPE_CHECKLIST
            || in_array(strtolower(trim((string) $this->response)), ['pass', 'fail', 'n/a', 'na'], true);
    }

    public function calculateResultStatus(): void
    {
        if ($this->value_type !== self::VALUE_NUMERIC || blank($this->response)) {
            $this->result_status = null;

            return;
        }

        if (! is_numeric($this->response)) {
            $this->result_status = self::RESULT_FAIL;

            return;
        }

        $value = (float) $this->response;
        $minimum = $this->acceptance_min === null ? null : (float) $this->acceptance_min;
        $maximum = $this->acceptance_max === null ? null : (float) $this->acceptance_max;

        $this->result_status = match ($this->acceptance_operator) {
            'between' => $minimum !== null && $maximum !== null && $value >= $minimum && $value <= $maximum ? self::RESULT_PASS : self::RESULT_FAIL,
            'gte' => $minimum !== null && $value >= $minimum ? self::RESULT_PASS : self::RESULT_FAIL,
            'lte' => $maximum !== null && $value <= $maximum ? self::RESULT_PASS : self::RESULT_FAIL,
            'eq' => $minimum !== null && $value === $minimum ? self::RESULT_PASS : self::RESULT_FAIL,
            default => null,
        };
    }
}
