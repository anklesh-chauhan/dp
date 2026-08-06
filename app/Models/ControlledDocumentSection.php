<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ControlledDocumentSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlledDocumentSection extends Model
{
    public const TYPE_TEXT = 'rich_text';

    public const TYPE_TABLE = 'structured_table';

    public const TYPE_CHECKLIST = 'checklist';

    public const TYPE_REPEATING_LOG = 'repeating_log';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    /** @use HasFactory<ControlledDocumentSectionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $section): void {
            $configuration = is_array($section->configuration) ? $section->configuration : [];
            $defaults = match ($section->section_type) {
                self::TYPE_TABLE => [
                    'columns' => 'Parameter, Specification, Result, Unit, Acceptance criteria',
                ],
                self::TYPE_CHECKLIST => [
                    'response_options' => 'Pass, Fail, N/A',
                    'columns' => 'Item, Response, Comments, Initials',
                ],
                self::TYPE_REPEATING_LOG => [
                    'frequency' => 'Daily',
                    'columns' => 'Date/Time, Reading, Initials, Remarks, Verified by',
                ],
                default => [],
            };
            $section->configuration = [...$defaults, ...$configuration];

            if ($section->isCompleted()) {
                $section->completed_at ??= now();
                $section->completed_by ??= auth()->id();

                if (filled($section->verified_by)) {
                    $section->verified_at ??= now();
                } else {
                    $section->verified_at = null;
                }

                return;
            }

            $section->completed_at = null;
            $section->completed_by = null;
            $section->verified_at = null;
        });

        static::updated(function (self $section): void {
            $trackedFields = [
                'execution_status',
                'completed_by',
                'completed_at',
                'execution_notes',
                'verified_by',
                'verified_at',
            ];
            $changes = array_intersect_key($section->getChanges(), array_flip($trackedFields));

            if ($changes === []) {
                return;
            }

            $oldValues = [];
            foreach (array_keys($changes) as $field) {
                $oldValues[$field] = $section->getOriginal($field);
            }

            SopAuditLog::query()->create([
                'document_id' => $section->document_id,
                'user_id' => auth()->id(),
                'action' => SopAuditLog::ACTION_SECTION_EXECUTION_UPDATED,
                'old_values' => $oldValues,
                'new_values' => $changes,
            ]);
        });
    }

    protected $fillable = ['document_id', 'title', 'section_order', 'section_type', 'execution_status', 'completed_by', 'completed_at', 'execution_notes', 'verified_by', 'verified_at', 'heading_level', 'content', 'configuration', 'include_in_toc', 'toc_title'];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
            'heading_level' => 'integer',
            'include_in_toc' => 'boolean',
            'completed_at' => 'datetime',
            'configuration' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function executionStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_NOT_APPLICABLE => 'Not applicable',
        ];
    }

    public function isCompleted(): bool
    {
        return in_array($this->execution_status, [self::STATUS_COMPLETED, self::STATUS_NOT_APPLICABLE], true);
    }

    public function isIndependentlyVerified(): bool
    {
        return $this->isCompleted()
            && $this->verified_by !== null
            && $this->verified_by !== $this->completed_by;
    }

    public function hasValidStructuredConfiguration(): bool
    {
        if ($this->section_type === self::TYPE_TABLE) {
            return filled($this->configuration['columns'] ?? null);
        }

        if ($this->section_type === self::TYPE_CHECKLIST) {
            return filled($this->configuration['columns'] ?? null)
                && filled($this->configuration['response_options'] ?? null);
        }

        if ($this->section_type === self::TYPE_REPEATING_LOG) {
            return filled($this->configuration['frequency'] ?? null)
                && filled($this->configuration['columns'] ?? null);
        }

        return true;
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return BelongsTo<ControlledDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }

    /** @return HasMany<ControlledDocumentSectionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ControlledDocumentSectionItem::class, 'section_id')->orderBy('item_order');
    }
}
