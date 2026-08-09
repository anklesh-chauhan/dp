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

    public const TYPE_SIGNATURES = 'signatures';

    public const TYPE_ANNEXURES = 'annexures';

    /** @use HasFactory<ControlledDocumentSectionFactory> */
    use HasFactory;

    protected $fillable = ['document_id', 'title', 'section_order', 'section_type', 'heading_level', 'content', 'configuration', 'include_in_toc', 'toc_title'];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
            'heading_level' => 'integer',
            'include_in_toc' => 'boolean',
            'configuration' => 'array',
        ];
    }

    public function requiresFieldDefinitions(): bool
    {
        return in_array($this->section_type, [self::TYPE_TABLE, self::TYPE_CHECKLIST], true);
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
